<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new \SetaPDF_Core_Writer_Http('font-loader.pdf', true);
// get a document instance
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

$loadedFonts = [];

/* This is a simple font-loader that is ignoring the font-family but simply switches between font-styles.
 * It is an urgent requirement to cache the instances for a document instance. Otherwise, you will create
 * and embed several minimal font subsets.
 */
$fontLoader = static function(\SetaPDF_Core_Document $document, $fontFamily, $fontStyle) use (&$loadedFonts, $assetsDirectory) {
    $cacheKey = $document->getInstanceIdent() . '_' . $fontStyle;
    if (!array_key_exists($cacheKey, $loadedFonts)) {
        $fontPath = $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans';
        switch ($fontStyle) {
            case 'B':
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $fontPath . '-Bold.ttf');
                break;
            case 'I':
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $fontPath . '-Oblique.ttf');
                break;
            case 'BI':
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $fontPath . '-BoldOblique.ttf');
                break;
            default:
                $font = new \SetaPDF_Core_Font_Type0_Subset($document, $fontPath . '.ttf');
        }
        $loadedFonts[$cacheKey] = $font;
    }
    return $loadedFonts[$cacheKey];
};

// now simply create a stam instance
$stamp = new SetaPDF_Stamper_Stamp_RichText($document, $fontLoader);
// pass an HTML like text to format the output
$stamp->setText(<<<HTML
    This <b>stamp</b> uses <span style="font-family: Anything;"><i>DejaVuSans</i></span> <b><i>throughout</i></b>!
HTML
);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);
// pass the stamp instance
$stamper->addStamp($stamp);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
