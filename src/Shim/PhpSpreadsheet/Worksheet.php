<?php

namespace PhpOffice\PhpSpreadsheet;

class Worksheet
{
    /**
     * @var array<int, array<int, mixed>>
     */
    private array $rows = [];

    /**
     * @param array<int, mixed> $values
     */
    public function fromArray(array $values, mixed $nullValue = null, string $startCell = 'A1'): void
    {
        if (preg_match('/(\d+)$/', $startCell, $matches) !== 1) {
            $rowIndex = 1;
        } else {
            $rowIndex = (int) $matches[1];
        }

        $this->rows[$rowIndex] = $values;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function getRows(): array
    {
        ksort($this->rows);

        return $this->rows;
    }
}
