<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$translateOptions = require 'options.php';

$value = displaySelect('Position & Translate:', $translateOptions);
$data = $translateOptions[$value];

$writer = new HttpWriter('positioning-and-translate.pdf', true);
$document = new Document($writer);
// let's add some pages for demonstration purpose
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
$stamp->setText('A simple example text to demonstrate positioning and $translateX and $translateY parameter.');

// add the stamp object on all pages on the given position
$stamper->addStamp(
    $stamp,
    $data['position'],
    Stamper::PAGES_ALL,
    $data['translateX'],
    $data['translateY']
);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
