<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$translations = [
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_ALL',
        'showOnPage' => SetaPDF_Stamper::PAGES_ALL
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_EVEN',
        'showOnPage' => SetaPDF_Stamper::PAGES_EVEN
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_ODD',
        'showOnPage' => SetaPDF_Stamper::PAGES_ODD
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_FIRST',
        'showOnPage' => SetaPDF_Stamper::PAGES_FIRST
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_LAST',
        'showOnPage' => SetaPDF_Stamper::PAGES_LAST
    ],
    [
        'displayValue' => "'2-' (2nd page until the last page)",
        'showOnPage' => '2-'
    ],
    [
        'displayValue' => "'1-5' (page 1 to 5)",
        'showOnPage' => '1-5'
    ],
    [
        'displayValue' => '[3, 5, 8, 99]',
        'showOnPage' => [3, 5, 8, 99]
    ],
    [
        'displayValue' => 'second last page (callback function)',
        'showOnPage' => function($pageNumber, $pageCount) {
            return $pageNumber === ($pageCount - 1);
        }
    ]
];

$value = displaySelect('Show on page:', $translations);
$data = $translations[$value];

$writer = new SetaPDF_Core_Writer_Http('positioning-and-translate.pdf', true);
$document = new SetaPDF_Core_Document($writer);
// let's add 2 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
for ($i = 100; $i > 0; $i--) {
    $pages->create(
        SetaPDF_Core_PageFormats::A4,
        ($i & 1) ? SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT : SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE
    );
}

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
    SetaPDF_Stamper::POSITION_LEFT_TOP,
    $data['showOnPage']
);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
