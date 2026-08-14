<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create an HTTP writer
$writer = new HttpWriter('stamped-and-encrypted.pdf', true);
// let's get the document
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$stamp = new TextStamp($font, 80);
// set a text
$stamp->setText('CONFIDENTIAL');
$stamp->setOpacity(.1);
$stamp->setTextColor('#FF0000');

// add the stamp to the stamper instance
$stamper->addStamp(
    $stamp,
    [
        'position' => Stamper::POSITION_CENTER_MIDDLE,
        'rotation' => 60
    ]
);

// execute the stamp process
$stamper->stamp();

// now simply create a security handler
\setasign\SetaPDF2\Core\SecHandler\Standard\Aes256::create(
    $document,
    'TopSecret',
    'MoreTopSecret' // no permissions granted (if respected by the viewer application)
);

// save and finish the document instance
$document->save()->finish();
