<?php

declare(strict_types=1);

use App\Http\Controllers\ExcelImportController;
use Illuminate\Support\Facades\Route;

Route::fallback(fn () => response()->json([
    'message' => 'Page Not Found.',
], 404));

Route::post('/upload-excel', [ExcelImportController::class, 'upload'])
    ->middleware('auth.basic');
