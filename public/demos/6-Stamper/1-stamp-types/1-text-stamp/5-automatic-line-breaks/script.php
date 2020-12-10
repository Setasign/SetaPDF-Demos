<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$text = "An example text with some more content and no explicit "
    . "line-breaks to be able to show the line break behavior.";

// we create a blank document to show the behavior
$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = new SetaPDF_Core_Document($writer);

// let's create 1 page for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(SetaPDF_Core_PageFormats::A4);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance WITHOUT width
$stampA = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stampA->setText('No width given: ' . $text);
$stampA->setAlign(SetaPDF_Core_Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampA);

// create a stamp instance WITH width
$stampB = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stampB->setText('Width given: ' . $text);
$stampB->setWidth(220);
$stampB->setAlign(SetaPDF_Core_Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampB, SetaPDF_Stamper::POSITION_LEFT_MIDDLE);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
