<?php

use setasign\SetaPDF2\Core\Core;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf'
];

$path = displayFiles($files);

$perSheet = 2;

if (($perSheet & ($perSheet - 1)) !== 0) {
    throw new InvalidArgumentException('Per page is not a square from 2');
}

$gridSizeShortSide = 1;
$gridSizeLongSide = 1;

// calculate grid size
for ($a = $perSheet; $a > 1; $a /= 2) {
    if ($gridSizeShortSide === $gridSizeLongSide) {
        $gridSizeShortSide *= 2;
    } else {
        $gridSizeLongSide = $gridSizeShortSide;
    }
}

// load the original document
$originalDocument = Document::loadByFilename($path);
// get the pages instance of the original document
$originalPages = $originalDocument->getCatalog()->getPages();

$page = $originalPages->getPage(1);

/** @var array $pageSize */
$pageSize = $page->getWidthAndHeight();

$longSide = $pageSize[0] > $pageSize[1] ? 0 : 1;
$shortSide = $longSide === 1 ? 0 : 1;

// create a new writer for the new document
$writer = new HttpWriter(basename($path), true);

// create a new document
$newDocument = new Document($writer);

// get the pages instance of the new document
$newPages = $newDocument->getCatalog()->getPages();

// store the original page count
$originalPageCount = $originalPages->count();

// determine how many pages need to be generated
$finalPageCount = ceil($originalPageCount / $perSheet);

// get the page size according to the orientation and page size
$newPageSize = [
    $shortSide => $pageSize[$shortSide] * $gridSizeShortSide,
    $longSide  => $pageSize[$longSide] * $gridSizeLongSide
];

// create the new pages
for ($newPageNumber = 1; $newPageNumber <= $finalPageCount; $newPageNumber++) {
    // create a new page
    $newPage = $newPages->create($newPageSize, PageFormats::ORIENTATION_AUTO);

    // prepare an offset to access the pages of the original document
    $pageOffset = ($newPageNumber - 1) * $perSheet;

    $pos = [
        $newPageSize[0],
        $pageSize[1]
    ];

    // iterate through the pages of the original document that should be placed onto the new created page
    for (
        $pageCounter = 1;
        $pageOffset + $pageCounter <= $originalPageCount && $pageCounter <= $perSheet;
        $pageCounter++
    ) {
        $originalPage = $originalPages->getPage($pageOffset + $pageCounter);

        $xObject = $originalPage->toXObject($newDocument);

        $xObject->draw(
            $newPage->getCanvas(),
            $newPage->getWidth() - $pos[0],
            $newPage->getHeight() - $pos[1],
            $pageSize[0],
            $pageSize[1]
        );

        $pos[0] -= $pageSize[0];
        if ($pos[0] < Core::FLOAT_COMPARISON_PRECISION) {
            $pos[1] += $pageSize[1];
            $pos[0] = $newPageSize[0];
        }
    }
}

$newDocument->save()->finish();