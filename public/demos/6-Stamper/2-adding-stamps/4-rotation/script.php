<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$rotationOptions = require 'options.php';

$value = displaySelect('Position & Rotation:', $rotationOptions);
$data = $rotationOptions[$value];

$writer = new SetaPDF_Core_Writer_Http('positioning-and-translate.pdf', true);
$document = new SetaPDF_Core_Document($writer);
// let's add 2 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(SetaPDF_Core_PageFormats::A4, SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT);
$pages->create(SetaPDF_Core_PageFormats::A4, SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 12);
$stamp->setBackgroundColor([0.5, 1, 1]);
$stamp->setBorderWidth(1);
$stamp->setPadding(2);
$stamp->setWidth(180);
$stamp->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
$stamp->setText('A simple example text to demonstrate rotation.');

// add the stamp object on all pages on the given position
$stamper->addStamp(
    $stamp,
    [
        'position' => $data['position'],
        'rotation' => $data['rotation']
    ]
);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
