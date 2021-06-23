<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new SetaPDF_Core_Writer_Http('smile.pdf', true);
// get a document instance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

// The text for our stamp should "look" like:
// "This document is licensed to <b>test@example.com</b> and was created on <i>www.setasign.com</i>."

// We need to draw the text style-by-style.
// First we prepare font instances:
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

$fontB = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans-Bold.ttf'
);

$fontI = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans-Oblique.ttf'
);

$text[] = ['This document is licensed to ', $font];
$text[] = ['test@example.com', $fontB];
$text[] = [' and was created on ', $font];
$text[] = ['www.setasign.com', $fontI];
$text[] = ['.', $font];

$fontSize = 10;
$height = $fontSize * 1.2;
$width = 0;

// calculate total width
foreach ($text as $textItem) {
    $width += ($textItem[1]->getGlyphsWidth($textItem[0], 'UTF-8') / 1000 * $fontSize);
}

// create a XObject
$xObject = SetaPDF_Core_XObject_Form::create($document, [0, 0, $width, $height]);
// get the Canvas
$canvas = $xObject->getCanvas();

// start the text output
$canvasText = $canvas->text()
    ->begin()
    ->moveToNextLine(0, -$text[0][1]->getDescent() / 1000 * $fontSize);

foreach ($text as $textItem) {
    /** @var SetaPDF_Core_Font_FontInterface $font */
    $font = $textItem[1];

    $canvasText
        ->setFont($font, $fontSize)
        ->showText($font->getCharCodes($textItem[0], 'UTF-8'));
}

$canvasText->end();

// create the stamp object for the XObject
$xObjectStamp = new SetaPDF_Stamper_Stamp_XObject($xObject);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);
// pass the stamp instance
$stamper->addStamp($xObjectStamp, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_TOP,
    'translateY' => -5
]);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
