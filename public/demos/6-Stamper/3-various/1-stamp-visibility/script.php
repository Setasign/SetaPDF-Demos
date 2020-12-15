<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's get access to the file
$reader = new SetaPDF_Core_Reader_File($assetsDirectory . '/pdfs/Brand-Guide.pdf');
// create a HTTP writer
$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
// let's get the document
$document = SetaPDF_Core_Document::load($reader, $writer);

// initiate a stamper instance
$stamper = new SetaPDF_Stamper($document);

// let's use a TrueType font for the stamp appearance:
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans-ExtraLight.ttf'
);

// initialize a text stamp which is not shown in print
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 8);
$stamp->setText("Downloaded: " . date("Y-m-d H:i") . "\nUser: tester\nEmail: test@example.com");
// set border color to dark gray
$stamp->setBorderColor([0.2235, 0.3922, 0.6863]);
// set border width to 0.5 which is very small
$stamp->setBorderWidth(0.5);
// set padding to 5
$stamp->setPadding(5);
// set visibility to "print only" through this the stamp is only visible on printed pdfs
$stamp->setVisibility(SetaPDF_Stamper_Stamp_Text::VISIBILITY_PRINT);

/**
 * dont stamp pages first and last page
 */
function callbackAllOddPagesWithoutFirstAndLast($pageNumber, $pageCount)
{
    return $pageNumber !== 1 && $pageNumber !== $pageCount && ($pageNumber & 1) === 1;
}

/**
 * dont stamp pages first and last page
 */
function callbackAllEvenPagesWithoutFirstAndLast($pageNumber, $pageCount)
{
    return $pageNumber !== 1 && $pageNumber !== $pageCount && ($pageNumber & 1) === 0;
}

// add stamp to left top on every odd page (without first and last page) and adjust the position
$stamper->addStamp($stamp, SetaPDF_Stamper::POSITION_LEFT_TOP, 'callbackAllOddPagesWithoutFirstAndLast', 45, -30);
// add stamp to right top on every even page (without first and last page) and adjust the position
$stamper->addStamp($stamp, SetaPDF_Stamper::POSITION_RIGHT_TOP, 'callbackAllEvenPagesWithoutFirstAndLast', -45, -30);
// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the resulting document
$document->save()->finish();
