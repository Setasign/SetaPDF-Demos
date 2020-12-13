<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$positions = [
    SetaPDF_Stamper::POSITION_LEFT_TOP => 'SetaPDF_Stamper::POSITION_LEFT_TOP',
    SetaPDF_Stamper::POSITION_LEFT_MIDDLE => 'SetaPDF_Stamper::POSITION_LEFT_MIDDLE',
    SetaPDF_Stamper::POSITION_LEFT_BOTTOM => 'SetaPDF_Stamper::POSITION_LEFT_BOTTOM',
    SetaPDF_Stamper::POSITION_CENTER_TOP => 'SetaPDF_Stamper::POSITION_CENTER_TOP',
    SetaPDF_Stamper::POSITION_CENTER_MIDDLE => 'SetaPDF_Stamper::POSITION_CENTER_MIDDLE',
    SetaPDF_Stamper::POSITION_CENTER_BOTTOM => 'SetaPDF_Stamper::POSITION_CENTER_BOTTOM',
    SetaPDF_Stamper::POSITION_RIGHT_TOP => 'SetaPDF_Stamper::POSITION_RIGHT_TOP',
    SetaPDF_Stamper::POSITION_RIGHT_MIDDLE => 'SetaPDF_Stamper::POSITION_RIGHT_MIDDLE',
    SetaPDF_Stamper::POSITION_RIGHT_BOTTOM => 'SetaPDF_Stamper::POSITION_RIGHT_BOTTOM'
];

$position = displaySelect('Position:', $positions);

$writer = new SetaPDF_Core_Writer_Http('positioning.pdf', true);
$document = new SetaPDF_Core_Document($writer);
// let's add 2 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(SetaPDF_Core_PageFormats::A4, SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT);
$pages->create(SetaPDF_Core_PageFormats::A4, SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stamp->setBackgroundColor([0.5, 1, 1]);
$stamp->setBorderWidth(1);
$stamp->setPadding(2);
$stamp->setText("A simple example text\nTo demonstrate positioning.");

// add the stamp object on all pages on the given position
$stamper->addStamp($stamp, $position);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
