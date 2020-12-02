<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new SetaPDF_Merger();

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Boombastic-Box.pdf',
    'outlinesConfig' => [
        SetaPDF_Merger::OUTLINES_TITLE => 'Boombastic Box',
        SetaPDF_Merger::OUTLINES_BOLD => true
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Fantastic-Speaker.pdf',
    'outlinesConfig' => [
        SetaPDF_Merger::OUTLINES_TITLE => 'Fantastic Speaker',
        SetaPDF_Merger::OUTLINES_ITALIC => true
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Noisy-Tube.pdf',
    'outlinesConfig' => [
        SetaPDF_Merger::OUTLINES_TITLE => 'Noisy Tube',
        SetaPDF_Merger::OUTLINES_ITALIC => true,
        SetaPDF_Merger::OUTLINES_BOLD => true,
        SetaPDF_Merger::OUTLINES_COLOR => [1, 0, 0] // RGB
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(SetaPDF_Core_Document_PageMode::USE_OUTLINES);

$document->setWriter(new SetaPDF_Core_Writer_Http('styled-bookmark-outline.pdf', true));
$document->save()->finish();
