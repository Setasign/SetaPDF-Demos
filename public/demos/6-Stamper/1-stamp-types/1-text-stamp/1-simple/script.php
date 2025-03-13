<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

$path = displayFiles($files);

$writer = new HttpWriter('stamped.pdf', true);
$document = Document::loadByFilename($path, $writer);

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

$fontSize = 12;

// create a stamp instance
$stamp = new TextStamp($font, $fontSize);
// set a text
$stamp->setText('A simple example text.');

// add the stamp to the stamper instance
$stamper->addStamp($stamp);
// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
