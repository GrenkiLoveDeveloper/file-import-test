<?php

declare(strict_types=1);

namespace App\Traits;

use Generator;
use Rap2hpoutre\FastExcel\FastExcel;
use RuntimeException;

trait ExcelChunkImportable {
    /**
     * Imports an Excel file in chunks.
     *
     * @param string $path The path to the Excel file.
     * @param int $chunkSize The size of each chunk.
     * @param callable $processChunk Callback to process each chunk of rows.
     * @param callable|null $transformRow Optional callback to map each row.
     */
    public function importUsingChunk(
        string $path,
        int $chunkSize,
        callable $processChunk,
        ?callable $transformRow = null
    ): void {
        if (! file_exists($path)) {
            throw new RuntimeException("File not found: {$path}");
        }
        $buffer = [];

        foreach ($this->yieldRows($path, $transformRow) as $rowWithMeta) {
            $buffer[] = $rowWithMeta;

            if (count($buffer) === $chunkSize) {
                $processChunk($buffer);
                $buffer = [];
            }
        }

        if (! empty($buffer)) {
            $processChunk($buffer);
        }
    }

    /**
     * Yields rows from the Excel file.
     *
     * @param string $path The path to the Excel file.
     * @param callable|null $transformRow Optional callback to map each row.
     * @return Generator Yields each row with its line number.
     */
    private function yieldRows(string $path, ?callable $transformRow = null): Generator {
        $line = 1;

        foreach ((new FastExcel)->withoutHeaders()->import($path) as $row) {
            $line++;
            if ($transformRow) {
                $row = $transformRow($row, $line);
            }
            yield ['row' => $row, 'line' => $line];
        }
    }
}
