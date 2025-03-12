<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Reader\FileReader;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Image as ImageStamp;
use setasign\SetaPDF2\Stamper\Stamp\Text as TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's get access to the file
$reader = new FileReader($assetsDirectory . '/pdfs/Brand-Guide.pdf');
// create an HTTP writer
$writer = new HttpWriter('stamped.pdf', true);
// let's get the document
$document = Document::load($reader, $writer);

// get pages helper
$pages = $document->getCatalog()->getPages();
// create a page in format A4 but don't append it
$page = $pages->create(PageFormats::A4, PageFormats::ORIENTATION_PORTRAIT, false);
// prepend the created page
$pages->prepend($page);

// initiate a stamper instance
$stamper = new Stamper($document);

// let's use a TrueType font for the stamp appearance:
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// initiate the text stamp
$stamp = new TextStamp($font, 20);
// fill the stamp with text
$stamp->setText(
    "SetaPDF-Stamper - Title Page - Demo\n"
    . "From: " . $_SERVER['REMOTE_ADDR'] . "\n"
    . "User: Tester\n"
    . "Date: " . date("Y-m-d H:i")
);
// center the text in textbox
$stamp->setAlign(Text::ALIGN_CENTER);
// define line height to 35
$stamp->setLineHeight(30);
// add stamp to stamper
$stamper->addStamp($stamp, Stamper::POSITION_CENTER_MIDDLE, Stamper::PAGES_FIRST);

// now we want to insert an image above the text

// define which image we want to stamp
$image = Image::getByPath($assetsDirectory . '/pdfs/camtown/Logo.png');
// initiate a image stamp
$stamp = new ImageStamp($image);
// stretch image stamp
$stamp->setDimensions(150, 30);
// Add stamp to stamper centered on the first page with a y translation of +80
$stamper->addStamp($stamp, Stamper::POSITION_CENTER_MIDDLE, Stamper::PAGES_FIRST, 0, 120);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the resulting document
$document->save(true)->finish();
