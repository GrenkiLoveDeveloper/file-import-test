<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ExcelImportService;
use App\Traits\ExcelChunkImportable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ImportExcelRowsJob implements ShouldBeUnique, ShouldQueue {
    use Dispatchable, ExcelChunkImportable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $filePath,
        protected string $importKey
    ) {}

    /**
     * Unique identifier for the job.
     *
     * @return string
     */
    public function uniqueId(): string {
        return $this->importKey;
    }

    /**
     * Execute the job.
     *
     * @param \App\Services\ExcelImportService $importService
     * @return void
     */
    public function handle(ExcelImportService $importService): void {
        $this->importExcelChunk(
            $this->filePath,
            1000,
            function (array $chunk) use ($importService): void {
                $importService->processChunk($chunk, $this->importKey);
            },
        );
        $importService->saveErrorReport();
        Storage::delete($this->filePath);
    }
}
