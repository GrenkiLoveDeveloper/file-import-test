<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportExcelRowsJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ExcelImportService {
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
}
