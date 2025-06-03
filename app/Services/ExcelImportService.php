<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExcelRowIndices;
use App\Jobs\ImportExcelRowsJob;
use App\Models\Row;
use App\Rules\DateValidator;
use App\Rules\IdValidator;
use App\Rules\NameValidator;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ExcelImportService {
    /**
     * error Messages.
     *
     * @var array<string>
     */
    protected array $errorMessages = [];

    protected int $processedCount = 0;

    /**
     * Start the asynchronous import of an Excel file.
     *
     * @param UploadedFile $file The uploaded Excel file.
     * @return string The progress key for tracking the import status.
     *
     * @throws RuntimeException If the file cannot be saved or found.
     */
    public function startAsyncImport(UploadedFile $file): string {
        $path = $file->store('imports');
        if ($path === false) {
            throw new RuntimeException('Не удалось сохранить загруженный файл');
        }

        if (! Storage::exists($path)) {
            throw new RuntimeException('Файл не найден после сохранения');
        }
        $progressKey = 'excel_import_progress_' . md5($path);

        ImportExcelRowsJob::dispatch($path, $progressKey)->onQueue('default');

        return $progressKey;
    }

    /**
     * Process a chunk of rows.
     *
     * @param array<array<mixed>> $chunk The chunk of rows to process.
     */
    public function processChunk(array $chunk, string $importKey): void {
        $this->processedCount += count($chunk);
        Redis::set($importKey, $this->processedCount);

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
     * Save and send Error report to file.
     *
     * @return void
     */
    public function saveErrorReport(): void {
        if (empty($this->errorMessages)) {
            return;
        }
        Log::error('Errors during import: ' . implode(', ', $this->errorMessages));
        $content = implode(PHP_EOL, $this->errorMessages);
        Storage::put('import-errors/result.txt', $content);
    }

    /**
     * Validate a single row of data.
     *
     * @param array<mixed> $row The row data.
     * @param int $line The line number in the file.
     * @return array{file_id: int, name: string, date: string}|null Returns the validated row or null if there are errors.
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

        /**
         * @var int $id
         * @var string $name
         */
        return [
            'file_id' => (int) $id,
            'name' => (string) $name,
            'date' => (string) Carbon::createFromFormat('d.m.Y', $date)?->format('Y-m-d'),
        ];
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
                    $this->errorMessages[] = "{$id} - Duplicate ID";

                    continue;
                }

                if (isset($seenInChunk[$id])) {
                    $this->errorMessages[] = "{$id} - Duplicate ID";

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
}
