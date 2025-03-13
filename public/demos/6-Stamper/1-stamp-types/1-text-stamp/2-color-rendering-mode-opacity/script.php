<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

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

$writer = new HttpWriter('stamped.pdf', true);
$document = Document::loadByFilename($path, $writer);

// create a stamper instance
$stamper = new Stamper($document);

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// another stamp will be printed on every page centered with the text "TOP SECRET" rotated
// by 50 degrees as a filled stroke text with transparency
$stamp = new TextStamp($font, 100);
$stamp->setText("TOP SECRET");

// set text color to red
$stamp->setTextColor([1, 0, 0]);

// set rendering mode to 2(fill, then stroke)
// @see PDF reference 32000-1:2008 9.3.6 Text Rendering Mode
$stamp->setRenderingMode(2);

// set transparency
$stamp->setOpacity(0.1);

// add stamp on all pages on position center_top rotated by 50 degrees
$stamper->addStamp($stamp, Stamper::POSITION_CENTER_MIDDLE, Stamper::PAGES_ALL, 0, 0, 50);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
