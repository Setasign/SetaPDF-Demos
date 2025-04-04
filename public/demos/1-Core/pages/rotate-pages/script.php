<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

$path = displayFiles($files);

// create a file writer
$writer = new HttpWriter('rotated.pdf', true);
// load document by filename
$document = Document::loadByFilename($path, $writer);

// get pages object
$pages = $document->getCatalog()->getPages();
// get page count
$pageCount = $pages->count();

for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
    // get page object for this page
    $page = $pages->getPage($pageNumber);

    // rotate by...
    $page->rotateBy(90);
}

// save and finish the document
$document->save()->finish();
