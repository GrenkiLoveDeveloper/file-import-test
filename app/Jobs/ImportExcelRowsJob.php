<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Traits\ExcelChunkImportable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ImportExcelRowsJob implements ShouldBeUnique, ShouldQueue {
    use Dispatchable, ExcelChunkImportable, InteractsWithQueue, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $filePath,
        protected string $importKey
    ) {}

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
            },
            fn (array $rowWithMeta, int $line) => $this->validateRow($rowWithMeta['row'], $line)
        );
    }

    protected function validateRow(array $row, int $line): ?array {}

    protected function processChunk(array $chunk): void {}

    protected function saveErrorReport(): void {}
}
