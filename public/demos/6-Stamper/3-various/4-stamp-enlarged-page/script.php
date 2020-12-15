<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's get access to the file
$reader = new SetaPDF_Core_Reader_File($assetsDirectory . '/pdfs/Brand-Guide.pdf');
// create a HTTP writer
$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
//$writer = new SetaPDF_Core_Writer_File('stamped.pdf');
// let's get the document
$document = SetaPDF_Core_Document::load($reader, $writer);

// get first page
$firstPage = $document->getCatalog()->getPages()->getPage(1);
// get actually boundary of the first page
$boundary = $firstPage->getBoundary();
// define the new boundary which is increased on the bottom by 50
$newBoundary = SetaPDF_Core_DataStructure_Rectangle::byArray(
    [$boundary->getLlx(), $boundary->getLly() - 55, $boundary->getUrx(), $boundary->getUry()]
);

// we will first need to enlarge the media box to resize the crop box(visible area)
// because in this document the crop box has the same size like the media box
// and everything which isn't in the media box wouldn't be displayed
$firstPage->setBoundary($newBoundary, SetaPDF_Core_PageBoundaries::MEDIA_BOX);
// now we can enlarge the crop box
$firstPage->setBoundary($newBoundary, SetaPDF_Core_PageBoundaries::CROP_BOX);


// initiate a stamper instance
$stamper = new SetaPDF_Stamper($document);
// initiate an custom font
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// initialize a text stamp
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 10);
$stamp->setText(
    "This file is downloaded at " . date("Y-m-d H:i") . " from \"" . $_SERVER['REMOTE_ADDR']
    . "\" by user \"Tester\" (e-mail: \"test@example.com\").\n"
    . "This file is a stamped demo file of the SetaPDF-Stamper."
);
// set the width of the stamp to the same width like the crop box
$stamp->setWidth($newBoundary->getWidth());
// center text
$stamp->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
// set padding to 5
$stamp->setPadding(10);

// add stamp on first page on position center-bottom
$stamper->addStamp($stamp, SetaPDF_Stamper::POSITION_CENTER_BOTTOM, SetaPDF_Stamper::PAGES_FIRST);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the resulting document
$document->save()->finish();
