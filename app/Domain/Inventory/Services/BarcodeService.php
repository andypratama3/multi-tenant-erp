<?php

namespace App\Domain\Inventory\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\Types\TypeCode128;

class BarcodeService
{
    // Supported barcode types — matches picqer/php-barcode-generator constants
    public const TYPE_C128 = 'C128';

    public const TYPE_EAN13 = 'EAN13';

    public const TYPE_EAN8 = 'EAN8';

    public const TYPE_UPCA = 'UPCA';

    /**
     * Generate a barcode as SVG string.
     *
     * @param  'C128'|'C128A'|'C128B'|'C128C'|'C32'|'C39'|'C39+'|'C39E'|'C39E+'|'C93'|'CODABAR'|'CODE11'|'EAN13'|'EAN2'|'EAN5'|'EAN8'|'I25'|'I25+'|'IMB'|'ITF14'|'KIX'|'MSI'|'MSI+'|'PHARMA'|'PHARMA2T'|'PLANET'|'POSTNET'|'RMS4CC'|'S25'|'S25+'|'TELEPENALPHA'|'TELEPENNUMERIC'|'UPCA'|'UPCE'  $type
     */
    public function svg(string $code, string $type = 'C128'): string
    {
        $generator = new BarcodeGeneratorSVG;

        /** @var 'C128'|'C128A'|'C128B'|'C128C'|'C32'|'C39'|'C39+'|'C39E'|'C39E+'|'C93'|'CODABAR'|'CODE11'|'EAN13'|'EAN2'|'EAN5'|'EAN8'|'I25'|'I25+'|'IMB'|'ITF14'|'KIX'|'MSI'|'MSI+'|'PHARMA'|'PHARMA2T'|'PLANET'|'POSTNET'|'RMS4CC'|'S25'|'S25+'|'TELEPENALPHA'|'TELEPENNUMERIC'|'UPCA'|'UPCE' $checkedType */
        $checkedType = $this->resolveType($type);

        return $generator->getBarcode($code, $checkedType);
    }

    /**
     * Generate a barcode as PNG binary string.
     *
     * @param  'C128'|'C128A'|'C128B'|'C128C'|'C32'|'C39'|'C39+'|'C39E'|'C39E+'|'C93'|'CODABAR'|'CODE11'|'EAN13'|'EAN2'|'EAN5'|'EAN8'|'I25'|'I25+'|'IMB'|'ITF14'|'KIX'|'MSI'|'MSI+'|'PHARMA'|'PHARMA2T'|'PLANET'|'POSTNET'|'RMS4CC'|'S25'|'S25+'|'TELEPENALPHA'|'TELEPENNUMERIC'|'UPCA'|'UPCE'  $type
     */
    public function png(string $code, string $type = 'C128', int $widthFactor = 2, int $height = 50): string
    {
        $generator = new BarcodeGeneratorPNG;

        /** @var 'C128'|'C128A'|'C128B'|'C128C'|'C32'|'C39'|'C39+'|'C39E'|'C39E+'|'C93'|'CODABAR'|'CODE11'|'EAN13'|'EAN2'|'EAN5'|'EAN8'|'I25'|'I25+'|'IMB'|'ITF14'|'KIX'|'MSI'|'MSI+'|'PHARMA'|'PHARMA2T'|'PLANET'|'POSTNET'|'RMS4CC'|'S25'|'S25+'|'TELEPENALPHA'|'TELEPENNUMERIC'|'UPCA'|'UPCE' $checkedType */
        $checkedType = $this->resolveType($type);

        return $generator->getBarcode($code, $checkedType, $widthFactor, $height);
    }

    /**
     * Generate a base64-encoded PNG for embedding in HTML/PDF.
     *
     * @param  'C128'|'C128A'|'C128B'|'C128C'|'C32'|'C39'|'C39+'|'C39E'|'C39E+'|'C93'|'CODABAR'|'CODE11'|'EAN13'|'EAN2'|'EAN5'|'EAN8'|'I25'|'I25+'|'IMB'|'ITF14'|'KIX'|'MSI'|'MSI+'|'PHARMA'|'PHARMA2T'|'PLANET'|'POSTNET'|'RMS4CC'|'S25'|'S25+'|'TELEPENALPHA'|'TELEPENNUMERIC'|'UPCA'|'UPCE'  $type
     */
    public function base64Png(string $code, string $type = 'C128'): string
    {
        return 'data:image/png;base64,'.base64_encode($this->png($code, $type));
    }

    /**
     * Resolve the type constant from TypeCode128 or use provided string.
     * Falls back to C128 for unknown types.
     *
     * @return 'C128'|'C128A'|'C128B'|'C128C'|'C32'|'C39'|'C39+'|'C39E'|'C39E+'|'C93'|'CODABAR'|'CODE11'|'EAN13'|'EAN2'|'EAN5'|'EAN8'|'I25'|'I25+'|'IMB'|'ITF14'|'KIX'|'MSI'|'MSI+'|'PHARMA'|'PHARMA2T'|'PLANET'|'POSTNET'|'RMS4CC'|'S25'|'S25+'|'TELEPENALPHA'|'TELEPENNUMERIC'|'UPCA'|'UPCE'
     */
    private function resolveType(string $type): string
    {
        $valid = ['C128', 'C128A', 'C128B', 'C128C', 'C32', 'C39', 'C39+', 'C39E', 'C39E+', 'C93',
            'CODABAR', 'CODE11', 'EAN13', 'EAN2', 'EAN5', 'EAN8', 'I25', 'I25+', 'IMB', 'ITF14',
            'KIX', 'MSI', 'MSI+', 'PHARMA', 'PHARMA2T', 'PLANET', 'POSTNET', 'RMS4CC', 'S25',
            'S25+', 'TELEPENALPHA', 'TELEPENNUMERIC', 'UPCA', 'UPCE'];

        return in_array($type, $valid, true) ? $type : 'C128';
    }
}
