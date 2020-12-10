<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',

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

// create a text stamp instance
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 10);
$stamp->setText("Personalized for john@example.com");
// set the border color to gray (e.g. as a hex value)
$stamp->setBorderColor('#c7c7c7');
// and width
$stamp->setBorderWidth(1);
// set background color to white (e.g. as an array of RGB values (0 - 1)
$stamp->setBackgroundColor([1, 1, 1]);
// set padding
$stamp->setPadding(3);
// set text color by an explicit color instance
$stamp->setTextColor(new SetaPDF_Core_DataStructure_Color_Rgb(56/255, 101/255, 174/255));

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_BOTTOM,
    'translateY' => 15
]);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
