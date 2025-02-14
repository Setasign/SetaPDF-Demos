<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Reader\FileReader;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf'
];

$path = displayFiles($files);

// create a reader
$reader = new FileReader($path);
// create a writer
$writer = new HttpWriter('delete-pages.pdf', true);
// create a document
$document = Document::load($reader, $writer);

// get the pages helper
$pages = $document->getCatalog()->getPages();
// or
// $pages = $document->getPages();

// delete all but the first page
while ($pages->count() > 1) {
    $pages->deletePage($pages->count());
}

// save the complete document
$document->save(false)->finish();