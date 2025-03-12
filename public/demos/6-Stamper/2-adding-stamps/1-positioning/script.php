<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Text as TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$positions = [
    Stamper::POSITION_LEFT_TOP => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_TOP',
    Stamper::POSITION_LEFT_MIDDLE => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_MIDDLE',
    Stamper::POSITION_LEFT_BOTTOM => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_BOTTOM',
    Stamper::POSITION_CENTER_TOP => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_TOP',
    Stamper::POSITION_CENTER_MIDDLE => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_MIDDLE',
    Stamper::POSITION_CENTER_BOTTOM => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_BOTTOM',
    Stamper::POSITION_RIGHT_TOP => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_TOP',
    Stamper::POSITION_RIGHT_MIDDLE => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_MIDDLE',
    Stamper::POSITION_RIGHT_BOTTOM => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_BOTTOM'
];

$position = displaySelect('Position:', $positions);

$writer = new HttpWriter('positioning.pdf', true);
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
$stamp->setText("A simple example text\nTo demonstrate positioning.");

// add the stamp object on all pages on the given position
$stamper->addStamp($stamp, $position);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
