<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    [
        'displayValue' => 'camtown/Laboratory-Report.pdf',
        'file' => $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    ],
    [
        'displayValue' => 'lenstown/Laboratory-Report.pdf',
        'file' => $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    ],
    [
        'displayValue' => 'etown/Laboratory-Report.pdf',
        'file' => $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    ],
    [
        'displayValue' => 'tektown/Laboratory-Report.pdf',
        'file' => $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf'
    ],
];

$path = displayFiles($files)['file'];

$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// an other stamp will printed on every page centered with the text "TOP SECRET" rotated
// by 50 degrees as a filled stroke text with transparency
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 100);
$stamp->setText("TOP SECRET");

// set text color to red
$stamp->setTextColor([1, 0, 0]);

// set rendering mode to 2(fill, then stroke)
// @see PDF reference 32000-1:2008 9.3.6 Text Rendering Mode
$stamp->setRenderingMode(2);

// set transparency
$stamp->setOpacity(0.1);

// add stamp on all pages on position center_top rotated by 50 degress
$stamper->addStamp($stamp, SetaPDF_Stamper::POSITION_CENTER_MIDDLE, SetaPDF_Stamper::PAGES_ALL, 0, 0, 50);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
