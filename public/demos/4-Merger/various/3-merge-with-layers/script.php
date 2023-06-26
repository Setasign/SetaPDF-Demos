<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new \SetaPDF_Merger();

// copy layer information from this document
$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/layers/rect+circle+polygon.pdf',
    'copyLayers' => true // default
]);

// don't copy layer information from this document
$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/layers/rect+circle+triangle.pdf',
    'copyLayers' => false
]);

// copy the same document a 2nd time but also copy layer information (default behavior)
$merger->addDocument(
    \SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/layers/rect+circle+triangle.pdf')
);

$merger->merge();

$document = $merger->getDocument();

$document->setWriter(new \SetaPDF_Core_Writer_Http('layers.pdf', true));
$document->save()->finish();
