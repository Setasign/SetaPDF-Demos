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

// define a fixed width
$width = 180;
// prepare some example texts
$texts = [
    'All stamps will always have the width of ' . $width . ' points.',
    'A short text.',
    'A bit longer text.',
    'A much longer text with some more words',
    'A very long text with very very much words to show how the font-size is reduced.',
    "Also a text\nwith\nseveral\nlines will\nwork.",
    'A',
    'B',
    'AB',
    'ABC'
];

// we create a blank document to show the behavior
$writer = new HttpWriter('stamped.pdf', true);
$document = new Document($writer);

// let's create pages for each text
$pages = $document->getCatalog()->getPages();
for ($i = count($texts); $i > 0; $i--) {
    $pages->create(PageFormats::A4);
}

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// now create individual text stamps placed on each page
foreach ($texts as $key => $text) {
    // Setting the font-size to -1 will let it be calculated automatically
    $stamp = new TextStamp($font, -1);
    $stamp->setBorderWidth(1);
    $stamp->setText($text);
    $stamp->setAlign(Text::ALIGN_CENTER);
    $stamp->setTextWidth($width);
    $stamp->setPadding(1);
    // the set a line-height, we can make use of the calculated font-size
    $stamp->setLineHeight($stamp->getFontSize() * 1.2);
    $stamper->addStamp(
        $stamp,
        Stamper::POSITION_CENTER_TOP, $key + 1
    );
}

$stamper->stamp();

// save and finish the document instance
$document->save()->finish();

