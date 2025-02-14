<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Text as TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$text = "An example text with some more content and no explicit "
    . "line-breaks to be able to show the line break behavior.";

// we create a blank document to show the behavior
$writer = new HttpWriter('stamped.pdf', true);
$document = new Document($writer);

// let's create 1 page for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(PageFormats::A4);

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance WITHOUT width
$stampA = new TextStamp($font, 12);
$stampA->setText('No width given: ' . $text);
$stampA->setAlign(Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampA);

// create a stamp instance WITH width
$stampB = new TextStamp($font, 12);
$stampB->setText('Width given: ' . $text);
$stampB->setTextWidth(220);
$stampB->setAlign(Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampB, Stamper::POSITION_LEFT_MIDDLE);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
