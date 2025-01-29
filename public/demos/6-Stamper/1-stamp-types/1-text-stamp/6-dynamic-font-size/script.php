<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// define a fixed width
$width = 180;
// prepare some example texts
$texts = [
    'All stamps will always have the width of ' . $width . ' points.',
    'A short text.',
    'A bit longer text.',
    'A much longer text with some more words',
    'A very long text with very very much words to show how the font-size is reduzed.',
    "Also a text\nwith\nseveral\nlines will\nwork.",
    'A',
    'B',
    'AB',
    'ABC'
];

// we create a blank document to show the behavior
$writer = new \SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = new \SetaPDF_Core_Document($writer);

// let's create pages for each text
$pages = $document->getCatalog()->getPages();
for ($i = count($texts); $i > 0; $i--) {
    $pages->create(\SetaPDF_Core_PageFormats::A4);
}

// create a stamper instance
$stamper = new \SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new \SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// now create individual text stamps placed on each page
foreach ($texts as $key => $text) {
    // Setting the font-size to -1 will let it be calculated automatically
    $stamp = new \SetaPDF_Stamper_Stamp_Text($font, -1);
    $stamp->setBorderWidth(1);
    $stamp->setText($text);
    $stamp->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
    $stamp->setTextWidth($width);
    $stamp->setPadding(1);
    // the set a line-height, we can make use of the calculated font-size
    $stamp->setLineHeight($stamp->getFontSize() * 1.2);
    $stamper->addStamp(
        $stamp,
        SetaPDF_Stamper::POSITION_CENTER_TOP, $key + 1
    );
}

$stamper->stamp();

// save and finish the document instance
$document->save()->finish();

