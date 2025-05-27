<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportExcelRowsJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class ExcelImportService {
    public function startAsyncImport(UploadedFile $file): string {
        $path = $file->store('imports');
        if ($path === false) {
            throw new RuntimeException('Не удалось сохранить загруженный файл');
        }

        if (! Storage::exists($path)) {
            throw new RuntimeException('Файл не найден после сохранения');
        }
        $progressKey = 'excel_import_progress_' . md5($path);

        // ImportExcelRowsJob::dispatch($path, $progressKey)
        //     ->onQueue('excel-imports');

        return $progressKey;
    }
}
