<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$rectColors = [
    'red' => '"red" rect',
    'blue' => '"blue" rect',
    'yellow' => '"yellow" rect',
    'green' => '"green" rect'
];

$rectColor = displaySelect('Crop to:', $rectColors);

// create a writer instance
$writer = new HttpWriter('cropped.pdf', true);

// create a document instance
$document = Document::loadByFilename($assetsDirectory . '/pdfs/misc/4-rects.pdf', $writer);

// get the pages helper
$pages = $document->getCatalog()->getPages();
// get access to page #1
$page = $pages->getPage(1);

// this is a hard value
$margin = 36;
// calculate the areas of each rect by some logic
$format = $page->getWidthAndHeight();
$position = [
    'red' => [$margin, $format[1] / 2, $format[0] / 2, $format[1] - $margin],
    'blue' => [$format[0] / 2 + $margin, $format[1] / 2, $format[0] - $margin, $format[1] - $margin],
    'yellow' => [$margin, $margin, $format[0] / 2, $format[1] / 2],
    'green' => [$format[0] / 2, $margin, $format[0] - $margin, $format[1]  / 2],
];

// resize all available page boxes
foreach (PageBoundaries::$all AS $boxName) {
    $box = $page->getBoundary($boxName, false);
    if ($box === false) {
        continue;
    }

    // reset the box to the new calculated value
    $page->setBoundary($position[$rectColor], $boxName, false);
}

$document->save()->finish();
