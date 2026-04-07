<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Xlsx
{
    public function __construct(private Spreadsheet $spreadsheet)
    {
    }

    public function save(string $filename): void
    {
        $handle = fopen($filename, 'wb');

        if ($handle === false) {
            throw new \RuntimeException(sprintf('Unable to write spreadsheet to "%s".', $filename));
        }

        foreach ($this->spreadsheet->getActiveSheet()->getRows() as $row) {
            fputcsv($handle, array_map(static fn ($value): string => (string) $value, $row));
        }

        fclose($handle);
    }
}
