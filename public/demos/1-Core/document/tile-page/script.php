<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf'
];

$path = displayFiles($files);

$tileCount = 4;
$pageNumber = 1;

if (($tileCount & ($tileCount - 1)) !== 0) {
    throw new InvalidArgumentException('per page is not a square from 2');
}

$gridSizeX = 1;
$gridSizeY = 1;

// calculate grid size
for ($a = $tileCount; $a > 1; $a /= 2) {
    if ($gridSizeY === $gridSizeX) {
        $gridSizeY *= 2;
    } else {
        $gridSizeX = $gridSizeY;
    }
}

// determine the orientation of the new document
if ($gridSizeX === $gridSizeY) {
    $orientation = SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT;
} else {
    $orientation = SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE;
}

// load the original document
$originalDocument = SetaPDF_Core_Document::loadByFilename($path);
// get the pages instance of the original document
$originalPages = $originalDocument->getCatalog()->getPages();

// create a new writer for the new document
$writer = new SetaPDF_Core_Writer_Http(basename($path), true);

// create a new document
$newDocument = new SetaPDF_Core_Document($writer);

// get the pages instance of the new document
$newPages = $newDocument->getCatalog()->getPages();


// get the page that needs to be resized
$originalPage = $originalPages->getPage($pageNumber);

// convert the page to an XObject
$pageXObject = $originalPage->toXObject($newDocument);

// get the width and height in the correct orientation
$pageFormat = SetaPDF_Core_PageFormats::getFormat($originalPage->getWidthAndHeight(), $orientation);

// calculate the new width and height for the XObject.
$objectWidth  = $pageFormat['width'] * $gridSizeX;
$objectHeight = $pageFormat['height'] * $gridSizeY;

// calculate the start position for drawing.
$currentX = 0;
$currentY = ($pageFormat['height']) * ($gridSizeY - 1);

// create the new pages
for ($newPageNumber = 1; $newPageNumber <= $tileCount; $newPageNumber++) {

    // create a new page
    $newPage = $newPages->create($originalPage->getWidthAndHeight(), $orientation);

    // draw the original page with its new width, height and position onto the new page
    $pageXObject->draw(
        $newPage->getCanvas(),
        $currentX * -1,
        $currentY * -1,
        $objectWidth,
        $objectHeight
    );

    // recalculate the current drawing position
    $currentX += ($objectWidth) / $gridSizeX;
    if ($currentX >= $objectWidth) {
        $currentX = 0;
        $currentY -= $objectHeight / $gridSizeY;

    }
}

$newDocument->getCatalog()->setPageLayout(SetaPDF_Core_Document_PageLayout::TWO_COLUMN_LEFT);
$newDocument->save()->finish();