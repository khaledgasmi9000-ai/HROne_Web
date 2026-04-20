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
        $commands[] = $this->fillRectangle(0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, [0.97, 0.96, 0.93]);
        $commands[] = $this->fillPolygon([[0, 595], [120, 595], [30, 320], [0, 320]], [0.92, 0.91, 0.88]);
        $commands[] = $this->fillPolygon([[0, 595], [72, 595], [0, 430]], [0.94, 0.93, 0.90]);
        $commands[] = $this->fillPolygon([[712, 595], [842, 595], [842, 0], [744, 0], [777, 165], [742, 430]], [0.17, 0.14, 0.62]);
        $commands[] = $this->fillPolygon([[740, 595], [782, 595], [842, 468], [842, 420], [760, 588]], [0.83, 0.69, 0.42]);
        $commands[] = $this->fillPolygon([[792, 0], [842, 120], [842, 0]], [0.83, 0.69, 0.42]);

        $commands[] = $this->centeredText('CERTIFICATE', 455, 58, 'F1', [0.06, 0.06, 0.07]);
        $commands[] = $this->centeredText('OF COMPLETION', 398, 28, 'F1', [0.17, 0.14, 0.62]);
        $commands[] = $this->centeredText('PROUDLY PRESENTED TO', 338, 17, 'F3', [0.12, 0.12, 0.12]);
        $commands[] = $this->centeredText(mb_strtoupper($participantName), 248, 44, 'F3', [0.04, 0.04, 0.04]);
        $commands[] = $this->drawLine(210, 226, 632, 226, [0.10, 0.10, 0.10], 2);
        $commands[] = $this->centeredText(sprintf('Pour avoir acheve avec succes la formation "%s"', $formationTitle), 194, 16, 'F4', [0.14, 0.14, 0.14]);
        $commands[] = $this->centeredText(sprintf('Niveau : %s', $level), 166, 17, 'F3', [0.12, 0.12, 0.12]);
        $commands[] = $this->centeredText(sprintf('Delivre le %s', $issuedAt), 140, 15, 'F4', [0.40, 0.42, 0.45]);

        $commands[] = $this->drawLine(105, 88, 262, 88, [0.10, 0.10, 0.10], 1.1);
        $commands[] = $this->drawLine(542, 88, 699, 88, [0.10, 0.10, 0.10], 1.1);
        $commands[] = $this->drawImage('SIG1', 112, 95, 130, 42);
        $commands[] = $this->drawImage('SIG2', 548, 95, 130, 42);
        $commands[] = $this->textAt('Agent RH', 146, 64, 17, 'F1', [0.10, 0.10, 0.10]);
        $commands[] = $this->textAt('Formateur', 578, 64, 17, 'F1', [0.10, 0.10, 0.10]);

        $commands[] = $this->textAt('HR', 58, 34, 26, 'F3', [0.16, 0.29, 0.60]);
        $commands[] = $this->textAt('One', 92, 34, 26, 'F1', [0.06, 0.06, 0.07]);
        $commands[] = $this->textAt(sprintf('Reference : %s', $certificateReference), 58, 18, 10, 'F4', [0.45, 0.46, 0.50]);

        return $this->buildPdf(implode("\n", array_filter($commands)));
    }

    private function buildPdf(string $content): string
    {
        $length = strlen($content);
        $signatureImages = [
            'SIG1' => $this->loadSignatureImage($this->getAgentRhSignatureImagePath()),
            'SIG2' => $this->loadSignatureImage($this->getTrainerSignatureImagePath()),
        ];
        $nextObjectId = 8;
        $imageResources = [];
        $signatureObjects = [];

        foreach ($signatureImages as $resourceName => $signatureImage) {
            $imageObjectId = $nextObjectId++;
            $softMaskObjectId = isset($signatureImage['soft_mask']) ? $nextObjectId++ : null;
            $imageResources[] = sprintf('/%s %d 0 R', $resourceName, $imageObjectId);
            $signatureObjects[] = [
                'image_object_id' => $imageObjectId,
                'soft_mask_object_id' => $softMaskObjectId,
                'image' => $signatureImage,
            ];
        }

        $contentObjectId = $nextObjectId;

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
        $objects[] = sprintf(
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.0F %.0F] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R /F4 7 0 R >> /XObject << %s >> >> /Contents %d 0 R >>\nendobj",
            self::PAGE_WIDTH,
            self::PAGE_HEIGHT,
            implode(' ', $imageResources),
            $contentObjectId
        );
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj";
        $objects[] = "7 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj";

        foreach ($signatureObjects as $signatureObject) {
            $objects[] = $this->buildImageObject(
                $signatureObject['image_object_id'],
                $signatureObject['image'],
                $signatureObject['soft_mask_object_id']
            );

            if ($signatureObject['soft_mask_object_id'] !== null) {
                /** @var array{width:int,height:int,data:string,color_space:string,bits_per_component:int,filter:string,decode_parms:string|null} $softMask */
                $softMask = $signatureObject['image']['soft_mask'];
                $objects[] = $this->buildImageObject($signatureObject['soft_mask_object_id'], $softMask, null);
            }
        }

        $objects[] = sprintf("%d 0 obj\n<< /Length %d >>\nstream\n%s\nendstream\nendobj", $contentObjectId, $length, $content);

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
     * @param array{
     *     width:int,
     *     height:int,
     *     data:string,
     *     color_space:string,
     *     bits_per_component:int,
     *     filter:string,
     *     decode_parms:string|null
     * } $image
     */
    private function buildImageObject(int $objectId, array $image, ?int $softMaskObjectId): string
    {
        $dictionary = sprintf(
            "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace %s /BitsPerComponent %d /Filter %s",
            $image['width'],
            $image['height'],
            $image['color_space'],
            $image['bits_per_component'],
            $image['filter']
        );

        if ($image['decode_parms'] !== null) {
            $dictionary .= ' /DecodeParms ' . $image['decode_parms'];
        }

        if ($softMaskObjectId !== null) {
            $dictionary .= sprintf(' /SMask %d 0 R', $softMaskObjectId);
        }

        $dictionary .= sprintf(' /Length %d >>', strlen($image['data']));

        return sprintf("%d 0 obj\n%s\nstream\n%s\nendstream\nendobj", $objectId, $dictionary, $image['data']);
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function centeredText(string $text, float $y, float $size, string $font, array $rgb): string
    {
        $x = (self::PAGE_WIDTH - $this->estimateTextWidth($text, $size, $font)) / 2;

        return $this->textAt($text, $x, $y, $size, $font, $rgb);
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function textAt(string $text, float $x, float $y, float $size, string $font, array $rgb): string
    {
        $encoded = $this->encodePdfText($text);

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

    private function drawImage(string $imageName, float $x, float $y, float $width, float $height): string
    {
        return sprintf(
            "q\n%.2F 0 0 %.2F %.2F %.2F cm\n/%s Do\nQ",
            $width,
            $height,
            $x,
            $y,
            $imageName
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

    /**
     * @param array<int, array{0: float, 1: float}> $points
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private function fillPolygon(array $points, array $rgb): string
    {
        if ($points === []) {
            return '';
        }

        $command = sprintf("q\n%.3F %.3F %.3F rg\n", $rgb[0], $rgb[1], $rgb[2]);
        $command .= sprintf("%.2F %.2F m\n", $points[0][0], $points[0][1]);

        for ($i = 1; $i < count($points); ++$i) {
            $command .= sprintf("%.2F %.2F l\n", $points[$i][0], $points[$i][1]);
        }

        $command .= "h\nf\nQ";

        return $command;
    }

    /**
     * @return array{
     *     width:int,
     *     height:int,
     *     data:string,
     *     color_space:string,
     *     bits_per_component:int,
     *     filter:string,
     *     decode_parms:string|null,
     *     soft_mask:array{
     *         width:int,
     *         height:int,
     *         data:string,
     *         color_space:string,
     *         bits_per_component:int,
     *         filter:string,
     *         decode_parms:string|null
     *     }|null
     * }
     */
    private function loadSignatureImage(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Signature image not found: %s', $path));
        }

        $info = getimagesize($path);

        if (!is_array($info)) {
            throw new \RuntimeException(sprintf('Unable to inspect signature image: %s', $path));
        }

        $type = $info[2] ?? null;

        if ($type === IMAGETYPE_JPEG) {
            $data = file_get_contents($path);

            if ($data === false) {
                throw new \RuntimeException(sprintf('Unable to read signature image: %s', $path));
            }

            return [
                'width' => (int) $info[0],
                'height' => (int) $info[1],
                'data' => $data,
                'color_space' => '/DeviceRGB',
                'bits_per_component' => 8,
                'filter' => '/DCTDecode',
                'decode_parms' => null,
                'soft_mask' => null,
            ];
        }

        if ($type === IMAGETYPE_PNG) {
            return $this->loadPngImage($path);
        }

        throw new \RuntimeException('Signature image must be a PNG or JPEG file.');
    }

    /**
     * @return array{
     *     width:int,
     *     height:int,
     *     data:string,
     *     color_space:string,
     *     bits_per_component:int,
     *     filter:string,
     *     decode_parms:string|null,
     *     soft_mask:array{
     *         width:int,
     *         height:int,
     *         data:string,
     *         color_space:string,
     *         bits_per_component:int,
     *         filter:string,
     *         decode_parms:string|null
     *     }|null
     * }
     */
    private function loadPngImage(string $path): array
    {
        $data = file_get_contents($path);

        if ($data === false) {
            throw new \RuntimeException(sprintf('Unable to read PNG signature image: %s', $path));
        }

        if (!str_starts_with($data, "\x89PNG\x0D\x0A\x1A\x0A")) {
            throw new \RuntimeException('Invalid PNG signature image.');
        }

        $offset = 8;
        $width = 0;
        $height = 0;
        $bitDepth = 0;
        $colorType = 0;
        $interlace = 0;
        $compressedImageData = '';
        $dataLength = strlen($data);

        while ($offset + 8 <= $dataLength) {
            $chunkLength = unpack('N', substr($data, $offset, 4));

            if (!is_array($chunkLength)) {
                throw new \RuntimeException('Unable to parse PNG chunk length.');
            }

            $chunkLength = (int) ($chunkLength[1] ?? 0);
            $chunkType = substr($data, $offset + 4, 4);
            $chunkData = substr($data, $offset + 8, $chunkLength);

            if (strlen($chunkData) !== $chunkLength) {
                throw new \RuntimeException('PNG signature image is truncated.');
            }

            if ($chunkType === 'IHDR') {
                $header = unpack('Nwidth/Nheight/CbitDepth/CcolorType/Ccompression/Cfilter/Cinterlace', $chunkData);

                if (!is_array($header)) {
                    throw new \RuntimeException('Unable to read the PNG header.');
                }

                $width = (int) ($header['width'] ?? 0);
                $height = (int) ($header['height'] ?? 0);
                $bitDepth = (int) ($header['bitDepth'] ?? 0);
                $colorType = (int) ($header['colorType'] ?? 0);
                $interlace = (int) ($header['interlace'] ?? 0);
            } elseif ($chunkType === 'IDAT') {
                $compressedImageData .= $chunkData;
            } elseif ($chunkType === 'IEND') {
                break;
            }

            $offset += 12 + $chunkLength;
        }

        if ($width <= 0 || $height <= 0) {
            throw new \RuntimeException('Invalid PNG dimensions for the signature image.');
        }

        if ($bitDepth !== 8) {
            throw new \RuntimeException('Only 8-bit PNG signature images are supported.');
        }

        if ($interlace !== 0) {
            throw new \RuntimeException('Interlaced PNG signature images are not supported.');
        }

        $channelsPerPixel = match ($colorType) {
            0 => 1,
            2 => 3,
            4 => 2,
            6 => 4,
            default => throw new \RuntimeException('Unsupported PNG color type for the signature image.'),
        };

        $inflated = $this->inflatePngData($compressedImageData);
        $scanlines = $this->unfilterPngScanlines($inflated, $width, $height, $channelsPerPixel);
        $stride = $width * $channelsPerPixel;
        $colorRows = '';
        $alphaRows = '';
        $hasAlpha = $colorType === 4 || $colorType === 6;
        $isRgb = $colorType === 2 || $colorType === 6;

        for ($rowIndex = 0; $rowIndex < $height; ++$rowIndex) {
            $row = substr($scanlines, $rowIndex * $stride, $stride);
            $colorRow = '';
            $alphaRow = '';

            for ($pixelOffset = 0; $pixelOffset < $stride; $pixelOffset += $channelsPerPixel) {
                if ($colorType === 6) {
                    $colorRow .= substr($row, $pixelOffset, 3);
                    $alphaRow .= $row[$pixelOffset + 3];
                } elseif ($colorType === 4) {
                    $colorRow .= $row[$pixelOffset];
                    $alphaRow .= $row[$pixelOffset + 1];
                } else {
                    $colorRow .= substr($row, $pixelOffset, $channelsPerPixel);
                }
            }

            $colorRows .= "\x00" . $colorRow;

            if ($hasAlpha) {
                $alphaRows .= "\x00" . $alphaRow;
            }
        }

        $compressedColorRows = gzcompress($colorRows);

        if ($compressedColorRows === false) {
            throw new \RuntimeException('Unable to compress PNG signature image data.');
        }

        $image = [
            'width' => $width,
            'height' => $height,
            'data' => $compressedColorRows,
            'color_space' => $isRgb ? '/DeviceRGB' : '/DeviceGray',
            'bits_per_component' => 8,
            'filter' => '/FlateDecode',
            'decode_parms' => $this->buildPngDecodeParms($isRgb ? 3 : 1, $width),
            'soft_mask' => null,
        ];

        if ($hasAlpha) {
            $compressedAlphaRows = gzcompress($alphaRows);

            if ($compressedAlphaRows === false) {
                throw new \RuntimeException('Unable to compress PNG alpha channel data.');
            }

            $image['soft_mask'] = [
                'width' => $width,
                'height' => $height,
                'data' => $compressedAlphaRows,
                'color_space' => '/DeviceGray',
                'bits_per_component' => 8,
                'filter' => '/FlateDecode',
                'decode_parms' => $this->buildPngDecodeParms(1, $width),
            ];
        }

        return $image;
    }

    private function buildPngDecodeParms(int $colors, int $columns): string
    {
        return sprintf('<< /Predictor 15 /Colors %d /BitsPerComponent 8 /Columns %d >>', $colors, $columns);
    }

    private function inflatePngData(string $data): string
    {
        if (function_exists('zlib_decode')) {
            $inflated = zlib_decode($data);
        } elseif (function_exists('gzuncompress')) {
            $inflated = gzuncompress($data);
        } else {
            throw new \RuntimeException('Zlib support is required to read the PNG signature.');
        }

        if (!is_string($inflated)) {
            throw new \RuntimeException('Unable to decode the PNG signature image data.');
        }

        return $inflated;
    }

    private function unfilterPngScanlines(string $data, int $width, int $height, int $bytesPerPixel): string
    {
        $rowLength = $width * $bytesPerPixel;
        $expectedLength = ($rowLength + 1) * $height;

        if (strlen($data) !== $expectedLength) {
            throw new \RuntimeException('Unexpected PNG image data length for the signature.');
        }

        $offset = 0;
        $previousRow = str_repeat("\x00", $rowLength);
        $result = '';

        for ($rowIndex = 0; $rowIndex < $height; ++$rowIndex) {
            $filterType = ord($data[$offset]);
            ++$offset;
            $row = substr($data, $offset, $rowLength);
            $offset += $rowLength;

            $decodedRow = $this->reversePngFilter($filterType, $row, $previousRow, $bytesPerPixel);
            $result .= $decodedRow;
            $previousRow = $decodedRow;
        }

        return $result;
    }

    private function reversePngFilter(int $filterType, string $row, string $previousRow, int $bytesPerPixel): string
    {
        $length = strlen($row);

        if ($filterType === 0) {
            return $row;
        }

        $decoded = '';

        for ($index = 0; $index < $length; ++$index) {
            $left = $index >= $bytesPerPixel ? ord($decoded[$index - $bytesPerPixel]) : 0;
            $up = ord($previousRow[$index]);
            $upLeft = $index >= $bytesPerPixel ? ord($previousRow[$index - $bytesPerPixel]) : 0;
            $value = ord($row[$index]);

            $reconstructed = match ($filterType) {
                1 => ($value + $left) & 0xFF,
                2 => ($value + $up) & 0xFF,
                3 => ($value + intdiv($left + $up, 2)) & 0xFF,
                4 => ($value + $this->paethPredictor($left, $up, $upLeft)) & 0xFF,
                default => throw new \RuntimeException(sprintf('Unsupported PNG filter type: %d', $filterType)),
            };

            $decoded .= chr($reconstructed);
        }

        return $decoded;
    }

    private function paethPredictor(int $left, int $up, int $upLeft): int
    {
        $prediction = $left + $up - $upLeft;
        $leftDistance = abs($prediction - $left);
        $upDistance = abs($prediction - $up);
        $upLeftDistance = abs($prediction - $upLeft);

        if ($leftDistance <= $upDistance && $leftDistance <= $upLeftDistance) {
            return $left;
        }

        if ($upDistance <= $upLeftDistance) {
            return $up;
        }

        return $upLeft;
    }

    private function estimateTextWidth(string $text, float $size, string $font): float
    {
        $factor = match ($font) {
            'F1' => 0.58,
            'F2' => 0.45,
            'F3' => 0.56,
            default => 0.49,
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

    private function getAgentRhSignatureImagePath(): string
    {
        return $this->resolveSignatureImagePath('certificate-signature-agent-rh');
    }

    private function getTrainerSignatureImagePath(): string
    {
        return $this->resolveSignatureImagePath('certificate-signature-formateur');
    }

    private function resolveSignatureImagePath(string $baseName): string
    {
        $imagesDirectory = dirname(__DIR__, 2) . '/public/images/';

        foreach ([
            $baseName . '.png',
            $baseName . '.jpg',
            'certificate-signature.png',
            'certificate-signature.jpg',
        ] as $filename) {
            $path = $imagesDirectory . $filename;

            if (is_file($path)) {
                return $path;
            }
        }

        return $imagesDirectory . $baseName . '.png';
    }
}
