<?php

namespace App\Service;

use App\Entity\Formation;

class CertificatePdfGenerator
{
    private const PAGE_WIDTH = 842.0;
    private const PAGE_HEIGHT = 595.0;

    /**
     * @param array{id:int,label:string,email:string} $participant
     */
    public function generate(Formation $formation, array $participant, string $certificateReference): string
    {
        $participantName = $participant['label'] !== '' ? $participant['label'] : 'Participant';
        $formationTitle = $formation->getTitre() ?: 'Formation';
        $level = $formation->getNiveau() ?: 'Non defini';
        $issuedAt = $this->formatIssuedDate();

        $commands = [];
        $commands[] = $this->fillRectangle(0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, [0.95, 0.97, 1.00]);
        $commands[] = $this->drawRectangle(18, 18, self::PAGE_WIDTH - 36, self::PAGE_HEIGHT - 36, [0.84, 0.66, 0.14], 4);
        $commands[] = $this->centeredText('CERTIFICAT DE PARTICIPATION', 500, 31, 'F1', [0.16, 0.29, 0.60]);
        $commands[] = $this->drawLine(164, 478, 678, 478, [0.84, 0.66, 0.14], 1.6);
        $commands[] = $this->centeredText('Nous certifions que', 420, 18, 'F2', [0.43, 0.44, 0.48]);
        $commands[] = $this->centeredText($participantName, 365, 25, 'F1', [0.16, 0.29, 0.60]);
        $commands[] = $this->centeredText('a participe avec succes a la formation', 300, 17, 'F2', [0.43, 0.44, 0.48]);
        $commands[] = $this->centeredText($this->wrapWithFrenchQuotes($formationTitle), 258, 18, 'F3', [0.84, 0.66, 0.14]);
        $commands[] = $this->centeredPair('Niveau :', $level, 205);
        $commands[] = $this->centeredText(sprintf('Delivre le %s', $issuedAt), 160, 15, 'F4', [0.53, 0.55, 0.59]);
        $commands[] = $this->textAt(sprintf('Reference : %s', $certificateReference), 60, 56, 11, 'F4', [0.53, 0.55, 0.59]);
        $commands[] = $this->drawLine(585, 108, 742, 108, [0.55, 0.56, 0.60], 1);
        $commands[] = $this->textAt('L Agent RH', 662, 84, 16, 'F2', [0.37, 0.39, 0.42]);

        return $this->buildPdf(implode("\n", array_filter($commands)));
    }

    private function buildPdf(string $content): string
    {
        $length = strlen($content);

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
        $objects[] = sprintf(
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.0F %.0F] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >> >> /Contents 8 0 R >>\nendobj",
            self::PAGE_WIDTH,
            self::PAGE_HEIGHT
        );
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "7 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "8 0 obj\n<< /Length $length >>\nstream\n$content\nendstream\nendobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < count($offsets); ++$i) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n$xrefOffset\n%%EOF";

        return $pdf;
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function centeredText(string $text, float $y, float $size, string $font, array $rgb): string
    {
        $encoded = $this->encodePdfText($text);
        $x = (self::PAGE_WIDTH - $this->estimateTextWidth($text, $size, $font)) / 2;

        return $this->textAtRaw($encoded, $x, $y, $size, $font, $rgb);
    }

    private function centeredPair(string $label, string $value, float $y): string
    {
        $size = 17.0;
        $gap = 8.0;
        $labelWidth = $this->estimateTextWidth($label, $size, 'F3');
        $valueWidth = $this->estimateTextWidth($value, $size, 'F4');
        $totalWidth = $labelWidth + $gap + $valueWidth;
        $startX = (self::PAGE_WIDTH - $totalWidth) / 2;

        return implode("\n", [
            $this->textAt($label, $startX, $y, $size, 'F3', [0.30, 0.32, 0.36]),
            $this->textAt($value, $startX + $labelWidth + $gap, $y, $size, 'F4', [0.30, 0.32, 0.36]),
        ]);
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function textAt(string $text, float $x, float $y, float $size, string $font, array $rgb): string
    {
        return $this->textAtRaw($this->encodePdfText($text), $x, $y, $size, $font, $rgb);
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function textAtRaw(string $encoded, float $x, float $y, float $size, string $font, array $rgb): string
    {
        return sprintf(
            "BT\n/%s %.2F Tf\n%.3F %.3F %.3F rg\n1 0 0 1 %.2F %.2F Tm\n(%s) Tj\nET",
            $font,
            $size,
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $x,
            $y,
            $encoded
        );
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function drawLine(float $x1, float $y1, float $x2, float $y2, array $rgb, float $width): string
    {
        return sprintf(
            "q\n%.3F %.3F %.3F RG\n%.2F w\n%.2F %.2F m\n%.2F %.2F l\nS\nQ",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $width,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function drawRectangle(float $x, float $y, float $width, float $height, array $rgb, float $lineWidth): string
    {
        return sprintf(
            "q\n%.3F %.3F %.3F RG\n%.2F w\n%.2F %.2F %.2F %.2F re\nS\nQ",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $lineWidth,
            $x,
            $y,
            $width,
            $height
        );
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function fillRectangle(float $x, float $y, float $width, float $height, array $rgb): string
    {
        return sprintf(
            "q\n%.3F %.3F %.3F rg\n%.2F %.2F %.2F %.2F re\nf\nQ",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $x,
            $y,
            $width,
            $height
        );
    }

    private function wrapWithFrenchQuotes(string $value): string
    {
        return sprintf('« %s »', $value);
    }

    private function estimateTextWidth(string $text, float $size, string $font): float
    {
        $factor = match ($font) {
            'F1' => 0.60,
            'F2' => 0.47,
            'F3' => 0.55,
            default => 0.50,
        };

        return mb_strlen($text) * $size * $factor;
    }

    private function encodePdfText(string $value): string
    {
        $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace('(', '\\(', $value);
        $value = str_replace(')', '\\)', $value);

        return preg_replace('/[^\x20-\x7E\x80-\xFF]/', '', $value) ?? '';
    }

    private function formatIssuedDate(): string
    {
        $months = [
            1 => 'janvier',
            2 => 'fevrier',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'aout',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'decembre',
        ];

        $month = $months[(int) date('n')] ?? date('m');

        return sprintf('%s %s %s', date('d'), $month, date('Y'));
    }
}
