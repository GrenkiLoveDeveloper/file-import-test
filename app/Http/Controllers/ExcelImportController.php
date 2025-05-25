<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;

class ExcelImportController extends Controller {
    public function upload(): JsonResponse {
        return response()->json([
            'message' => 'test',
        ], 200);
    }
}
