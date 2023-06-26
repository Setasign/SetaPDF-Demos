<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = new \SetaPDF_Core_Document();
$document->setWriter(new \SetaPDF_Core_Writer_Http('structured-bookmark-outline.pdf', true));
// show outlines when document opens
$document->getCatalog()->setPageMode(\SetaPDF_Core_Document_PageMode::USE_OUTLINES);

// create a root outline item
$outlines = $document->getCatalog()->getOutlines();
$root = \SetaPDF_Core_Document_OutlinesItem::create($document, 'Products');
$outlines->appendChild($root);

// initiate the merger with the prepared document instance
$merger = new \SetaPDF_Merger($document);

// add files from the file system
$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Boombastic-Box.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_TITLE => 'Boombastic Box',
        \SetaPDF_Merger::OUTLINES_PARENT => $root
    ]
]);

// let's remember the item (id)
$parent = $merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Fantastic-Speaker.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_TITLE => 'Fantastic Speaker',
        \SetaPDF_Merger::OUTLINES_PARENT => $root
    ]
]);

// now add this item below the previous one
$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Noisy-Tube.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_TITLE => 'Noisy Tube',
        \SetaPDF_Merger::OUTLINES_PARENT => $parent
    ]
]);

// merger
$merger->merge();

$document->save()->finish();
