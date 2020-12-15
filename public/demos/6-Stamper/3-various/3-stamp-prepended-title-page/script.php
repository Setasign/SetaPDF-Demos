<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's get access to the file
$reader = new SetaPDF_Core_Reader_File($assetsDirectory . '/pdfs/Brand-Guide.pdf');
// create a HTTP writer
$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
// let's get the document
$document = SetaPDF_Core_Document::load($reader, $writer);

// get pages helper
$pages = $document->getCatalog()->getPages();
// create a page in format A4 but don't append it
$page = $pages->create(SetaPDF_Core_PageFormats::A4, SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT, false);
// prepend the created page
$pages->prepend($page);

// initiate a stamper instance
$stamper = new SetaPDF_Stamper($document);

// let's use a TrueType font for the stamp appearance:
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// initiate the text stamp
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 20);
// fill the stamp with text
$stamp->setText(
    "SetaPDF-Stamper - Title Page - Demo\n"
    . "From: " . $_SERVER['REMOTE_ADDR'] . "\n"
    . "User: Tester\n"
    . "Date: " . date("Y-m-d H:i")
);
// center the text in textbox
$stamp->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
// define line height to 35
$stamp->setLineHeight(30);
// add stamp to stamper
$stamper->addStamp($stamp, SetaPDF_Stamper::POSITION_CENTER_MIDDLE, SetaPDF_Stamper::PAGES_FIRST);

// now we want to insert an image above the text

// define which image we want to stamp
$image = SetaPDF_Core_Image::getByPath($assetsDirectory . '/pdfs/camtown/Logo.png');
// initiate a image stamp
$stamp = new SetaPDF_Stamper_Stamp_Image($image);
// stretch image stamp
$stamp->setDimensions(150, 30);
// Add stamp to stamper centered on the first page with an y translation of +80
$stamper->addStamp($stamp, SetaPDF_Stamper::POSITION_CENTER_MIDDLE, SetaPDF_Stamper::PAGES_FIRST, 0, 120);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the resulting document
$document->save(true)->finish();
