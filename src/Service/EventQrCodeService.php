<?php

namespace App\Service;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

class EventQrCodeService
{
    public function buildParticipationQr(string $participationData): ResultInterface
    {
        $qrCode = new QrCode(
            data: $participationData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(15, 23, 42), // #0f172a
            backgroundColor: new Color(255, 255, 255)
        );

        $writer = extension_loaded('gd') ? new PngWriter() : new SvgWriter();

        return $writer->write($qrCode);
    }
}
