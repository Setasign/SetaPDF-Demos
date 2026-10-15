<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageBoundaries;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Logo.pdf',
    $assetsDirectory . '/pdfs/camtown/Logo-rotated-90.pdf',
    $assetsDirectory . '/pdfs/camtown/Logo-rotated-180.pdf',
    $assetsDirectory . '/pdfs/camtown/Logo-rotated-270.pdf',
];

$path = displayFiles($files);

// create a document instance
$document = Document::loadByFilename($path);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// iterate through the document page by page and get some properties
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    echo 'Page No.: ' . $pageNo . '<br >';

    // get the page object
    $page = $pages->getPage($pageNo);

    // print the page rotation value
    echo 'Rotation: ' . $page->getRotation() . '<br />';

    echo '<br /><br />';
}