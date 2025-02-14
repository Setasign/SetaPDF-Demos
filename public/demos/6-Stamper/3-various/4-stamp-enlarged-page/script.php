<?php

use setasign\SetaPDF2\Core\DataStructure\Rectangle;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Core\Reader\FileReader;
use setasign\SetaPDF2\Core\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Text as TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/misc/boxes/[1000 500 -1000 -500]-R90.pdf',
    $assetsDirectory . '/pdfs/misc/boxes/[1000 500 -1000 -500]-R-90.pdf',
    $assetsDirectory . '/pdfs/misc/rotated/180.pdf'
];

$path = displayFiles($files);

// let's get access to the file
$reader = new FileReader($path);
// create an HTTP writer
$writer = new HttpWriter('stamped.pdf', true);
//$writer = new \setasign\SetaPDF2\Core\Writer\FileWriter('stamped.pdf');
// let's get the document
$document = Document::load($reader, $writer);

// get first page
$firstPage = $document->getCatalog()->getPages()->getPage(1);
// get actually boundary of the first page
$boundary = $firstPage->getBoundary();
$rotation = $firstPage->getRotation();

// define the new boundary which is increased on the bottom by 55
switch ($rotation) {
    case 0:
        $newBoundary = Rectangle::byArray(
            [$boundary->getLlx(), $boundary->getLly() - 55, $boundary->getUrx(), $boundary->getUry()]
        );
        break;
    case 90:
        $newBoundary = Rectangle::byArray(
            [$boundary->getLlx(), $boundary->getLly(), $boundary->getUrx() + 55, $boundary->getUry()]
        );
        break;
    case 180:
        $newBoundary = Rectangle::byArray(
            [$boundary->getLlx(), $boundary->getLly(), $boundary->getUrx(), $boundary->getUry() + 55]
        );
        break;
    case 270:
        $newBoundary = Rectangle::byArray(
            [$boundary->getLlx() - 55, $boundary->getLly(), $boundary->getUrx(), $boundary->getUry()]
        );
        break;
}

// we will first need to enlarge the media box to resize the crop box(visible area)
// because in this document the crop box has the same size as the media box
// and everything which isn't in the media box wouldn't be displayed
$firstPage->setBoundary($newBoundary, PageBoundaries::MEDIA_BOX);
// now we can enlarge the crop box
$firstPage->setBoundary($newBoundary, PageBoundaries::CROP_BOX);


// initiate a stamper instance
$stamper = new Stamper($document);
// initiate a custom font
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// initialize a text stamp
$stamp = new TextStamp($font, 10);
$stamp->setText(
    "This file is downloaded at " . date("Y-m-d H:i") . " from \"" . $_SERVER['REMOTE_ADDR']
    . "\" by user \"Tester\" (e-mail: \"test@example.com\").\n"
    . "This file is a stamped demo file of the SetaPDF-Stamper."
);
// set the width of the stamp to the same width as the crop box
$stamp->setTextWidth($newBoundary->getWidth());
// center text
$stamp->setAlign(Text::ALIGN_CENTER);
// set padding to 5
$stamp->setPadding(10);

// add stamp on first page on position center-bottom
$stamper->addStamp($stamp, Stamper::POSITION_CENTER_BOTTOM, Stamper::PAGES_FIRST);

// stamp the document with all previously added stamps
$stamper->stamp();

// save and finish the resulting document
$document->save()->finish();
