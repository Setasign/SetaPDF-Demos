<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create an HTTP writer
$writer = new HttpWriter('artifact.pdf', true);
//$writer = new \setasign\SetaPDF2\Core\Writer\FileWriter('artifact.pdf');
// let's get the document
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

//--- Create a text stamp and wrap it in a Tagged stamp instance ---//

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$textStamp = new TextStamp($font, 10);

// simply define to mark the stamp as an artifact
$textStamp->setMarkAsArtifact();

// set a text
$textStamp->setText('Personalized for John Dow (jon.dow@example.com)');

// add the stamp to the stamper instance
$stamper->addStamp($textStamp, [
    'position' => Stamper::POSITION_CENTER_TOP,
    'translateX' => 2,
    'translateY' => -2
]);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
