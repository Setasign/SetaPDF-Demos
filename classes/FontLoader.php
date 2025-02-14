<?php

namespace com\setasign\SetaPDF\Demos;

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\Type0\Subset;

/**
 * This is a simple, straight forward font-loader implementation.
 * It should give you an idea of how to create your own.
 */
class FontLoader
{
    /**
     * @var Subset[]
     */
    protected $loadedFonts = [];

    /**
     * @var string
     */
    protected $assetsDirectory;

    /**
     * @param $assetsDirectory
     * @param $loadedFonts
     */
    public function __construct($assetsDirectory, &$loadedFonts = [])
    {
        $this->assetsDirectory = $assetsDirectory;
        $this->loadedFonts = &$loadedFonts;
    }

    /**
     * This is the method that is called when a font is requested.
     *
     * @param Document $document
     * @param string $fontFamily
     * @param string $fontStyle
     * @return Subset
     */
    public function __invoke(Document $document, string $fontFamily, string $fontStyle)
    {
        $cacheKey = $document->getInstanceIdent() . '_' . $fontFamily . '_' . $fontStyle;
        if (!array_key_exists($cacheKey, $this->loadedFonts)) {
            $dejavuFontPath = $this->assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans';
            if ($fontFamily === 'DejaVuSans' && $fontStyle === 'B') {
                $font = new Subset($document, $dejavuFontPath . '-Bold.ttf');
            } elseif ($fontFamily === 'DejaVuSans' && $fontStyle === 'I') {
                $font = new Subset($document, $dejavuFontPath . '-Oblique.ttf');
            } elseif ($fontFamily === 'DejaVuSans' && $fontStyle === 'BI') {
                $font = new Subset($document, $dejavuFontPath . '-BoldOblique.ttf');
            } else {
                $font = new Subset($document, $dejavuFontPath . '.ttf');
            }

            $this->loadedFonts[$cacheKey] = $font;
        }
        return $this->loadedFonts[$cacheKey];
    }
}
