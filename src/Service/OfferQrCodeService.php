<?php

namespace App\Service;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

class OfferQrCodeService
{
    public function buildOfferDetailsQr(string $detailsUrl): ResultInterface
    {
        $qrCode = new QrCode(
            data: $detailsUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 360,
            margin: 14,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(11, 79, 138),
            backgroundColor: new Color(255, 255, 255)
        );

        $writer = extension_loaded('gd') ? new PngWriter() : new SvgWriter();

        return $writer->write($qrCode);
    }
}
