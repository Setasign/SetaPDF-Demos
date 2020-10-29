<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/products/All.pdf',
    $assetsDirectory . '/pdfs/tektown/products/All-Collection.pdf',
    $assetsDirectory . '/pdfs/tektown/products/All-Portfolio.pdf',
];

$path = displayFiles($files);

// create a document
$document = SetaPDF_Core_Document::loadByFilename($path);

$catalog = $document->getCatalog();
$dictionary = $catalog->getDictionary();
if ($dictionary && $dictionary->offsetExists('Collection')) {
    echo 'This document IS a portable collection.';
} else {
    echo 'This document is NOT a portable collection.';
}
