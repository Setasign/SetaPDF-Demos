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

$text = "An example text with some more content\n"
    . "and line-breaks to be able to align\nthe text at all.";

// we create a blank document to show the behavior
$writer = new HttpWriter('stamped.pdf', true);
$document = new Document($writer);

// let's create 3 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(PageFormats::A4);
$pages->create(PageFormats::A4);
$pages->create(PageFormats::A4);

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance left aligned
$stampLeft = new TextStamp($font, 12);
$stampLeft->setText($text);
$stampLeft->setAlign(Text::ALIGN_LEFT);
$stamper->addStamp($stampLeft);

// create a stamp instance centered
$stampCenter = new TextStamp($font, 12);
$stampCenter->setText($text);
$stampCenter->setAlign(Text::ALIGN_CENTER);
$stamper->addStamp($stampCenter, [
    'position' => Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => 140
]);

// create a stamp instance justified
$stampCenter = new TextStamp($font, 12);
$stampCenter->setText($text);
$stampCenter->setAlign(Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampCenter, [
    'position' => Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => -140
]);

// create a stamp instance right aligned
$stampLeft = new TextStamp($font, 12);
$stampLeft->setText($text);
$stampLeft->setAlign(Text::ALIGN_RIGHT);
$stamper->addStamp($stampLeft, Stamper::POSITION_RIGHT_BOTTOM);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
