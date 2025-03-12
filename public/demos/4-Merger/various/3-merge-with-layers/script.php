<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new Merger();

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
$merger->addDocument(Document::loadByFilename($assetsDirectory . '/pdfs/layers/rect+circle+triangle.pdf'));

$merger->merge();

$document = $merger->getDocument();

$document->setWriter(new HttpWriter('layers.pdf', true));
$document->save()->finish();
