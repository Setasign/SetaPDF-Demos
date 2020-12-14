<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$translations = [
    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_LEFT_TOP & $translateX = 20, $translateY = -20',
        'position' => SetaPDF_Stamper::POSITION_LEFT_TOP,
        'translateX' => 20,
        'translateY' => -20
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_CENTER_TOP & $translateX = 0, $translateY = -20',
        'position' => SetaPDF_Stamper::POSITION_CENTER_TOP,
        'translateX' => 0,
        'translateY' => -20
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_RIGHT_TOP & $translateX = -20, $translateY = -20',
        'position' => SetaPDF_Stamper::POSITION_RIGHT_TOP,
        'translateX' => -20,
        'translateY' => -20
    ],

    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_LEFT_MIDDLE & $translateX = 20, $translateY = 0',
        'position' => SetaPDF_Stamper::POSITION_LEFT_MIDDLE,
        'translateX' => 20,
        'translateY' => 0
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_RIGHT_MIDDLE & $translateX = -20, $translateY = 0',
        'position' => SetaPDF_Stamper::POSITION_RIGHT_MIDDLE,
        'translateX' => -20,
        'translateY' => 0
    ],

    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_LEFT_BOTTOM & $translateX = 20, $translateY = 20',
        'position' => SetaPDF_Stamper::POSITION_LEFT_BOTTOM,
        'translateX' => 20,
        'translateY' => 20
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_CENTER_BOTTOM & $translateX = 0, $translateY = 20',
        'position' => SetaPDF_Stamper::POSITION_CENTER_BOTTOM,
        'translateX' => 0,
        'translateY' => 20
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::POSITION_RIGHT_BOTTOM & $translateX = -20, $translateY = 20',
        'position' => SetaPDF_Stamper::POSITION_RIGHT_BOTTOM,
        'translateX' => -20,
        'translateY' => 20
    ],
];

$value = displaySelect('Position & Translate:', $translations);
$data = $translations[$value];

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
$stamp->setText('A simple example text to demonstrate positioning and $translateX and $translateY parameter.');

// add the stamp object on all pages on the given position
$stamper->addStamp(
    $stamp,
    $data['position'],
    SetaPDF_Stamper::PAGES_ALL,
    $data['translateX'],
    $data['translateY']
);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
