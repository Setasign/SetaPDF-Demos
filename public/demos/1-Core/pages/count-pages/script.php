<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

$pages = $document->getCatalog()->getPages();
$pageCount = $pages->count();
// or
// $pageCount = count($pages);

echo 'The document "' . basename($_GET['f']) . '" has ' .
    ($pageCount === 1 ? '1 page' : $pageCount . ' pages');
