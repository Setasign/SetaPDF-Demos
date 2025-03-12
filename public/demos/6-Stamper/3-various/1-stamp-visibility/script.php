<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Reader\FileReader;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
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

// initiate a stamper instance
$stamper = new Stamper($document);

// let's use a TrueType font for the stamp appearance:
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans-ExtraLight.ttf'
);

// initialize a text stamp which is not shown in print
$stamp = new TextStamp($font, 8);
$stamp->setText("Downloaded: " . date("Y-m-d H:i") . "\nUser: tester\nEmail: test@example.com");
// set border color to dark gray
$stamp->setBorderColor([0.2235, 0.3922, 0.6863]);
// set border width to 0.5 which is very small
$stamp->setBorderWidth(0.5);
// set padding to 5
$stamp->setPadding(5);
// set visibility to "print only" through this the stamp is only visible on printed pdfs
$stamp->setVisibility(TextStamp::VISIBILITY_PRINT);

/**
 * don't stamp pages first and last page
 */
function callbackAllOddPagesWithoutFirstAndLast(int $pageNumber, int $pageCount): bool
{
    return $pageNumber !== 1 && $pageNumber !== $pageCount && ($pageNumber & 1) === 1;
}

/**
 * don't stamp pages first and last page
 */
function callbackAllEvenPagesWithoutFirstAndLast(int $pageNumber, int $pageCount): bool
{
    return $pageNumber !== 1 && $pageNumber !== $pageCount && ($pageNumber & 1) === 0;
}

// add stamp to left top on every odd page (without first and last page) and adjust the position
$stamper->addStamp($stamp, Stamper::POSITION_LEFT_TOP, 'callbackAllOddPagesWithoutFirstAndLast', 45, -30);
// add stamp to right top on every even page (without first and last page) and adjust the position
$stamper->addStamp($stamp, Stamper::POSITION_RIGHT_TOP, 'callbackAllEvenPagesWithoutFirstAndLast', -45, -30);
// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the resulting document
$document->save()->finish();
