<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',

];

$path = displayFiles($files);

$writer = new \SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = \SetaPDF_Core_Document::loadByFilename($path, $writer);

// create a stamper instance
$stamper = new \SetaPDF_Stamper($document);

require_once $classesDirectory . '/FontLoader.php';
$fontLoader = new \com\setasign\SetaPDF\Demos\FontLoader($assetsDirectory);

// create a rich-text stamp instance
$stamp = new \SetaPDF_Stamper_Stamp_RichText($document, $fontLoader);
$stamp->setDefaultFontFamily('DejaVuSans');
$stamp->setText('Personalized for <b style="color:#0f0f0f;">john@example.com</b>');
// set the border color to gray (e.g. as a hex value)
$stamp->setBorderColor('#c7c7c7');
// and width
$stamp->setBorderWidth(1);
// set background color to light-gray (e.g. as an array of RGB values (0 - 1)
$stamp->setBackgroundColor([.95, .95, .95]);
// set padding
$stamp->setPadding(3);
// set default text color by an explicit color instance
$stamp->setDefaultTextColor(new \SetaPDF_Core_DataStructure_Color_Rgb(56/255, 101/255, 174/255));

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'position' => \SetaPDF_Stamper::POSITION_CENTER_BOTTOM,
    'translateY' => 15
]);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
