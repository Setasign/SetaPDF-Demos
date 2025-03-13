<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$rotationOptions = require 'options.php';

$value = displaySelect('Position & Rotation:', $rotationOptions);
$data = $rotationOptions[$value];

$writer = new HttpWriter('positioning-and-translate.pdf', true);
$document = new Document($writer);
// let's add 2 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(PageFormats::A4, PageFormats::ORIENTATION_PORTRAIT);
$pages->create(PageFormats::A4, PageFormats::ORIENTATION_LANDSCAPE);

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$stamp = new TextStamp($font, 12);
$stamp->setBackgroundColor([0.5, 1, 1]);
$stamp->setBorderWidth(1);
$stamp->setPadding(2);
$stamp->setTextWidth(180);
$stamp->setAlign(Text::ALIGN_CENTER);
$stamp->setText('A simple example text to demonstrate rotation.');

// add the stamp object on all pages on the given position
$stamper->addStamp(
    $stamp,
    [
        'position' => $data['position'],
        'rotation' => $data['rotation']
    ]
);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
