<?php

use setasign\SetaPDF2\Demos\FontLoader;
use setasign\SetaPDF2\Core\DataStructure\Color\Rgb;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\RichTextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',

];

$path = displayFiles($files);

$writer = new HttpWriter('stamped.pdf', true);
$document = Document::loadByFilename($path, $writer);

// create a stamper instance
$stamper = new Stamper($document);

require_once $classesDirectory . '/FontLoader.php';
$fontLoader = new FontLoader($assetsDirectory);

// create a rich-text stamp instance
$stamp = new RichTextStamp($document, $fontLoader);
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
$stamp->setDefaultTextColor(new Rgb(56/255, 101/255, 174/255));

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'position' => Stamper::POSITION_CENTER_BOTTOM,
    'translateY' => 15
]);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
