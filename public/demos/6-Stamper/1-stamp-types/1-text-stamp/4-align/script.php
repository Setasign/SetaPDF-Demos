<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$text = "An example text with some more content\n"
    . "and line-breaks to be able to align\nthe text at all.";

// we create a blank document to show the behavior
$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = new SetaPDF_Core_Document($writer);

// let's create 3 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(SetaPDF_Core_PageFormats::A4);
$pages->create(SetaPDF_Core_PageFormats::A4);
$pages->create(SetaPDF_Core_PageFormats::A4);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance left aligned
$stampLeft = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stampLeft->setText($text);
$stampLeft->setAlign(SetaPDF_Core_Text::ALIGN_LEFT);
$stamper->addStamp($stampLeft);

// create a stamp instance centered
$stampCenter = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stampCenter->setText($text);
$stampCenter->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
$stamper->addStamp($stampCenter, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => 140
]);

// create a stamp instance justified
$stampCenter = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stampCenter->setText($text);
$stampCenter->setAlign(SetaPDF_Core_Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampCenter, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => -140
]);

// create a stamp instance right aligned
$stampLeft = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stampLeft->setText($text);
$stampLeft->setAlign(SetaPDF_Core_Text::ALIGN_RIGHT);
$stamper->addStamp($stampLeft, SetaPDF_Stamper::POSITION_RIGHT_BOTTOM);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
