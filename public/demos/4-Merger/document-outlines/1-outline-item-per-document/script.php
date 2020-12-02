<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new SetaPDF_Merger();

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Boombastic-Box.pdf',
    'outlinesConfig' => 'Boombastic Box'
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Fantastic-Speaker.pdf',
    'outlinesConfig' => 'Fantastic Speaker'
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Noisy-Tube.pdf',
    // or through a config array
    'outlinesConfig' => [
        SetaPDF_Merger::OUTLINES_TITLE => 'Noisy Tube'
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(SetaPDF_Core_Document_PageMode::USE_OUTLINES);

$document->setWriter(new SetaPDF_Core_Writer_Http('simple-bookmark-outline.pdf', true));
$document->save()->finish();
