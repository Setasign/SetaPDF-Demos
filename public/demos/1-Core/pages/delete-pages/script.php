<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf'
];

$path = displayFiles($files);

// create a reader
$reader = new SetaPDF_Core_Reader_File($path);
// create a writer
$writer = new SetaPDF_Core_Writer_Http('delete-pages.pdf', true);
// create a document
$document = SetaPDF_Core_Document::load($reader, $writer);

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