<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

$path = displayFiles($files);

$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

$fontSize = 12;

// create a stamp instance
$stamp = new SetaPDF_Stamper_Stamp_Text($font, $fontSize);
// set a text
$stamp->setText('A simple example text.');

// add the stamp to the stamper instance
$stamper->addStamp($stamp);
// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
