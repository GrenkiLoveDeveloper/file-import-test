<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\ImportExcelRowsJob;
use Illuminate\Http\UploadedFile;

final class ExcelImportService {
    public function startAsyncImport(UploadedFile $file): void {
        $path = $file->store('imports');
        ImportExcelRowsJob::dispatch($path)
            ->onQueue('excel-imports');
    }
}
