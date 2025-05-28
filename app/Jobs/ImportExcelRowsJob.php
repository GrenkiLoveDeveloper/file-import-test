<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Row;
use App\Rules\DateValidator;
use App\Traits\ExcelChunkImportable;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ImportExcelRowsJob implements ShouldBeUnique, ShouldQueue {
    use Dispatchable, ExcelChunkImportable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Summary of errorMessages.
     *
     * @var array<string>
     */
    protected array $errorMessages = [];

    protected int $processedCount = 0;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $filePath,
        protected string $importKey
    ) {}

    public function __destruct() {
        $this->saveErrorReport();
        Storage::delete($this->filePath);
    }

    public function uniqueId(): string {
        return $this->importKey;
    }

    /**
     * Execute the job.
     */
    public function handle(): void {
        Log::info('Джоб запущен');

        $this->importUsingChunk(
            $this->filePath,
            1000, function (array $chunk): void {
                $this->processChunk($chunk);
                Redis::set($this->importKey, $this->processedCount);
            },
            fn (array $rowWithMeta, int $line) => $this->validateRow($rowWithMeta['row'], $line)
        );
    }

    /**
     * Validate a single row of data.
     *
     * @param array<mixed> $row The row data.
     * @param int $line The line number in the file.
     * @return array<mixed>|null Returns the validated row or null if there are errors.
     */
    protected function validateRow(array $row, int $line): ?array {
        $errors = [];

        $id = $row['id'] ?? null;
        $name = $row['name'] ?? null;
        $date = $row['date'] ?? null;

        if (! DateValidator::isValidDate($date, 'd.m.Y')) {
            $errors[] = 'Invalid date';
        }

        if (! empty($errors)) {
            $this->errorMessages[] = "{$line} - " . implode(', ', $errors);

            return null;
        }

        return [
            // 'file_id' => (int) $id,
            'name' => $name,
            'date' => $date,
            'line' => $line,
        ];
    }

    protected function processChunk(array $chunk): void {
        $validRows = collect($chunk)->filter(fn ($item) => $item['row'] !== null)->values();

        if ($validRows->isEmpty()) {
            return;
        }

        $fileIds = $validRows->pluck('row.file_id')->unique()->toArray();

        $existing = Row::whereIn('file_id', $fileIds)->pluck('file_id')->toArray();
        // $existing = array_flip($existing);

        $insertData = [];

        foreach ($validRows as $item) {
            $row = $item['row'];
            $line = $row['line'];

            if (isset($existing[$row['file_id']])) {
                $this->errorMessages[] = "{$line} - Duplicate ID: {$row['file_id']}";

                continue;
            }

            $insertData[] = [
                'file_id' => $row['file_id'],
                'name' => $row['name'],
                'date' => Carbon::createFromFormat('d.m.Y', $row['date']),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $existing[$row['file_id']] = true;
        }

        if (! empty($insertData)) {
            Row::insert($insertData);
            $this->processedCount += count($insertData);
        }
    }

    protected function saveErrorReport(): void {
        if (empty($this->errorMessages)) {
            return;
        }

        $content = implode(PHP_EOL, $this->errorMessages);

        Storage::put('import-errors/result.txt', $content);

    }
}
