<?php

declare(strict_types=1);

namespace App\Traits;

use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;
use Generator;
use Illuminate\Support\Facades\Storage;

trait ExcelChunkImportable {
    /**
     * Summary of readExcelInChunks.
     *
     * @param string $filePath
     * @param int $chunkSize
     * @return Generator
     */
    public function readExcelInChunks(string $filePath, int $chunkSize): Generator {
        $isFirstRow = true;
        $line = 1;
        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open(Storage::path($filePath));

        $chunk = [];

        foreach ($reader->getSheetIterator() as $sheet) {

            foreach ($sheet->getRowIterator() as $row) {
                $line++;
                $cells = $row->toArray();

                if ($isFirstRow) {
                    $isFirstRow = false;

                    continue;
                }

                $chunk[] = ['data' => $cells, 'line' => $line];

                if (count($chunk) === $chunkSize) {
                    yield $chunk;
                    $chunk = [];
                }
            }
        }

        if (! empty($chunk)) {
            yield $chunk;
        }

        $reader->close();
    }

    /**
     * Processes an Excel file in chunks.
     *
     * @param string $filePath The path to the Excel file.
     * @param int $chunkSize The size of each chunk.
     * @param callable $processChunk Callback to process each chunk of rows.
     */
    public function processChunks(string $filePath, int $chunkSize, callable $processChunk): void {
        foreach ($this->readExcelInChunks($filePath, $chunkSize) as $chunk) {
            $processChunk($chunk);
        }
    }
}
