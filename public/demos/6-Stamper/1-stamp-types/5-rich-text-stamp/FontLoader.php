<?php
/* This file returns a default font loader instance.
 * The implementation uses the DejaVuSans font-family and also uses its regular version as a fallback if
 * another font-family is requested.
 */

$loadedFonts = [];

return static function (SetaPDF_Core_Document $document, $fontFamily, $fontStyle) use (&$loadedFonts, $assetsDirectory) {
    $cacheKey = $document->getInstanceIdent() . '_' . $fontFamily . '_' . $fontStyle;
    if (!array_key_exists($cacheKey, $loadedFonts)) {
        $dejaVufontPath = $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans';
        if ($fontFamily === 'DejaVuSans' && $fontStyle === 'B') {
            $font = new SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '-Bold.ttf');
        } elseif ($fontFamily === 'DejaVuSans' && $fontStyle === 'I') {
            $font = new SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '-Oblique.ttf');
        } elseif ($fontFamily === 'DejaVuSans' && $fontStyle === 'BI') {
            $font = new SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '-BoldOblique.ttf');
        } else {
            $font = new SetaPDF_Core_Font_Type0_Subset($document, $dejaVufontPath . '.ttf');
        }
        $loadedFonts[$cacheKey] = $font;
    }
    return $loadedFonts[$cacheKey];
};
