<?php

namespace App\Service;

use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class CvTextExtractorService
{
    public function extractFromBase64(string $fileName, string $mimeType, string $base64Content): string
    {
        $cleanBase64 = $this->normalizeBase64($base64Content);
        $binary = base64_decode($cleanBase64, true);
        if (!is_string($binary) || $binary === '') {
            throw new \RuntimeException('Le contenu du CV est invalide.');
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractFromPdf($binary),
            'docx' => $this->extractFromDocx($binary),
            'txt' => $this->normalizeText($binary),
            default => $this->extractByMime($mimeType, $binary),
        };
    }

    private function extractByMime(string $mimeType, string $binary): string
    {
        $mime = strtolower(trim($mimeType));

        return match (true) {
            str_contains($mime, 'pdf') => $this->extractFromPdf($binary),
            str_contains($mime, 'wordprocessingml') || str_contains($mime, 'officedocument') => $this->extractFromDocx($binary),
            str_contains($mime, 'text') => $this->normalizeText($binary),
            default => throw new \RuntimeException('Format CV non supporte. Utilisez PDF, DOCX ou TXT.'),
        };
    }

    private function extractFromPdf(string $binary): string
    {
        $parser = new Parser();
        $document = $parser->parseContent($binary);

        return $this->normalizeText($document->getText());
    }

    private function extractFromDocx(string $binary): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'cv_docx_');
        if ($tmpPath === false) {
            throw new \RuntimeException('Impossible de preparer la lecture du CV DOCX.');
        }

        $docxPath = $tmpPath . '.docx';
        @unlink($tmpPath);
        file_put_contents($docxPath, $binary);

        try {
            $phpWord = IOFactory::load($docxPath, 'Word2007');
            $chunks = [];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if ($element instanceof TextRun) {
                        foreach ($element->getElements() as $child) {
                            if ($child instanceof Text) {
                                $chunks[] = $child->getText();
                            }
                        }
                        continue;
                    }

                    if ($element instanceof Text) {
                        $chunks[] = $element->getText();
                    }
                }
            }

            return $this->normalizeText(implode("\n", $chunks));
        } finally {
            @unlink($docxPath);
        }
    }

    private function normalizeBase64(string $value): string
    {
        $value = trim($value);
        if (str_contains($value, ',')) {
            $parts = explode(',', $value, 2);
            $value = $parts[1] ?? $value;
        }

        return preg_replace('/\s+/', '', $value) ?? '';
    }

    private function normalizeText(string $value): string
    {
        $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
