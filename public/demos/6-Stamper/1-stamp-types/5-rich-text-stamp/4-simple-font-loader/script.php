<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\Type0\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\RichTextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('font-loader.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

$loadedFonts = [];

/* This is a simple font-loader that is ignoring the font-family but simply switches between font-styles.
 * It is an urgent requirement to cache the instances for a document instance. Otherwise, you will create
 * and embed several minimal font subsets.
 */
$fontLoader = static function(Document $document, $fontFamily, $fontStyle) use (&$loadedFonts, $assetsDirectory) {
    $cacheKey = $document->getInstanceIdent() . '_' . $fontStyle;
    if (!array_key_exists($cacheKey, $loadedFonts)) {
        $fontPath = $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans';
        switch ($fontStyle) {
            case 'B':
                $font = new Subset($document, $fontPath . '-Bold.ttf');
                break;
            case 'I':
                $font = new Subset($document, $fontPath . '-Oblique.ttf');
                break;
            case 'BI':
                $font = new Subset($document, $fontPath . '-BoldOblique.ttf');
                break;
            default:
                $font = new Subset($document, $fontPath . '.ttf');
        }
        $loadedFonts[$cacheKey] = $font;
    }
    return $loadedFonts[$cacheKey];
};

// now simply create a stamp instance
$stamp = new RichTextStamp($document, $fontLoader);
// pass an HTML like text to format the output
$stamp->setText(<<<HTML
    This <b>stamp</b> uses <span style="font-family: Anything;"><i>DejaVuSans</i></span> <b><i>throughout</i></b>!
HTML
);

// create a stamper instance
$stamper = new Stamper($document);
// pass the stamp instance
$stamper->addStamp($stamp);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
