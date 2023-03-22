<?php

namespace com\setasign\SetaPDF\Demos;

/**
 * This is a simple, straight forward font-loader implementation.
 * It should give you an idea of how to create your own.
 */
class FontLoader
{
    /**
     * @var \SetaPDF_Core_Font_Type0_Subset[]
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
     * @param \SetaPDF_Core_Document $document
     * @param string $fontFamily
     * @param string $fontStyle
     * @return \SetaPDF_Core_Font_Type0_Subset
     */
    public function __invoke(\SetaPDF_Core_Document $document, $fontFamily, $fontStyle)
    {
        $cacheKey = $document->getInstanceIdent() . '_' . $fontFamily . '_' . $fontStyle;
        if (!array_key_exists($cacheKey, $this->loadedFonts)) {
            $dejaVufontPath = $this->assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans';
            if ($fontFamily === 'DejaVuSans' && $fontStyle === 'B') {
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '-Bold.ttf');
            } elseif ($fontFamily === 'DejaVuSans' && $fontStyle === 'I') {
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '-Oblique.ttf');
            } elseif ($fontFamily === 'DejaVuSans' && $fontStyle === 'BI') {
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '-BoldOblique.ttf');
            } else {
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '.ttf');
            }

            $this->loadedFonts[$cacheKey] = $font;
        }
        return $this->loadedFonts[$cacheKey];
    }
}
