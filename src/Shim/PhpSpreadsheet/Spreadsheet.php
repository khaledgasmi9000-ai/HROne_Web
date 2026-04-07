<?php

namespace PhpOffice\PhpSpreadsheet;

class Spreadsheet
{
    private Worksheet $activeSheet;

    public function __construct()
    {
        $this->activeSheet = new Worksheet();
    }

    public function getActiveSheet(): Worksheet
    {
        return $this->activeSheet;
    }
}
