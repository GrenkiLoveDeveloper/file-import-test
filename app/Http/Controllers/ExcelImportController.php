<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UploadExcelRequest;
use App\Services\ExcelImportService;
use Illuminate\Http\JsonResponse;

class ExcelImportController extends Controller {
    public function __construct(protected ExcelImportService $excelImportService) {}

    /**
     * Handle the incoming request to upload an Excel file.
     *
     * @param \App\Http\Requests\UploadExcelRequest $request
     * @return JsonResponse
     */
    public function upload(UploadExcelRequest $request): JsonResponse {

        $file = $request->file('file'); // @phpstan-ignore-line

        $progressKey = $this->excelImportService->startAsyncImport($file);

        return response()->json([
            'message' => 'Импорт запущен',
            'progress_key' => $progressKey,
        ], 200);
    }
}
