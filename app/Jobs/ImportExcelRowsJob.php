<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ExcelRowIndices;
use App\Models\Row;
use App\Rules\DateValidator;
use App\Rules\IdValidator;
use App\Rules\NameValidator;
use App\Traits\ExcelChunkImportable;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
        // Storage::delete($this->filePath);
    }

    public function uniqueId(): string {
        return $this->importKey;
    }

    /**
     * Execute the job.
     */
    public function handle(): void {
        $this->processChunks(
            $this->filePath,
            1000,
            function (array $chunk): void {
                $this->processChunk($chunk);
            },
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
        $format = 'd.m.Y';

        $id = $row[ExcelRowIndices::ID->value];
        $name = $row[ExcelRowIndices::NAME->value] ?? '';
        $date = $row[ExcelRowIndices::DATE->value];

        if (! isset($id)) {
            $errors[] = 'ID is required';
        } elseif (! IdValidator::isValid($id)) {
            $errors[] = 'ID must be a positive integer';
        }

        if (! isset($date)) {
            $errors[] = 'Date is required';
        } elseif (! DateValidator::isValidDate($date, $format)) {
            $errors[] = "Date must be valid and in format {$format}";
        }

        // Todo: уточнить если пустое поле
        if (! NameValidator::isValidName($name)) {
            $errors[] = 'Name must contain only English letters and spaces';
        }

        if (! empty($errors)) {
            $this->errorMessages[] = "{$line} - " . implode(', ', $errors);

            return null;
        }

        return [
            'file_id' => $id,
            'name' => $name,
            'date' => Carbon::createFromFormat('d.m.Y', $date)?->format('Y-m-d'),
        ];
    }

    /**
     * Process a chunk of rows.
     *
     * @param array<array<mixed>> $chunk The chunk of rows to process.
     */
    protected function processChunk(array $chunk): void {
        $this->processedCount += count($chunk);
        Redis::set($this->importKey, $this->processedCount);

        $validatedData = [];

        foreach ($chunk as $row) {

            if (array_key_exists('line', $row) && array_key_exists('data', $row)) {
                $validatedRow = $this->validateRow($row['data'], $row['line']);

                if ($validatedRow !== null) {
                    $validatedData[] = $validatedRow;
                }
            }
        }

        if (! empty($validatedData)) {
            $this->upsertData($validatedData);
        }
    }

    /**
     * Upsert the validated data into the database.
     *
     * @param array<array{file_id: int, name: string, date: string}> $data
     */
    protected function upsertData(array $data): void {

        DB::transaction(function () use ($data): void {
            $fileIds = array_column($data, 'file_id');

            $existingIds = Row::whereIn('file_id', $fileIds)
                ->pluck('file_id')
                ->flip()
                ->all();

            $seenInChunk = [];
            $filteredData = [];

            foreach ($data as $row) {

                $id = (int) $row['file_id'];

                if (isset($existingIds[$id])) {
                    $this->errorMessages[] = "{$id} - Duplicate ID, already exists in DB";

                    continue;
                }

                if (isset($seenInChunk[$id])) {
                    $this->errorMessages[] = "{$id} - Duplicate ID in current chunk";

                    continue;
                }

                $seenInChunk[$id] = true;
                $filteredData[] = $row;
            }

            if (! empty($filteredData)) {
                Row::insert($filteredData);
            }
        });
    }

    protected function saveErrorReport(): void {
        if (empty($this->errorMessages)) {
            return;
        }

        $content = implode(PHP_EOL, $this->errorMessages);
        Log::debug('Строка не прошла валидацию', ['line' => $content]);
        Storage::put('import-errors/result.txt', $content);
    }
}
