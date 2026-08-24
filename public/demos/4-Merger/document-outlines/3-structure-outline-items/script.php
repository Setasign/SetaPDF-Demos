<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\OutlinesItem;
use setasign\SetaPDF2\Core\Document\PageMode;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = new Document();
$document->setWriter(new HttpWriter('structured-bookmark-outline.pdf', true));
// show outlines when document opens
$document->getCatalog()->setPageMode(PageMode::USE_OUTLINES);

// create a root outline item
$outlines = $document->getCatalog()->getOutlines();
$root = OutlinesItem::create($document, 'Products');
$outlines->appendChild($root);

// initiate the merger with the prepared document instance
$merger = new Merger($document);

// add files from the file system
$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Boombastic-Box.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Boombastic Box',
        Merger::OUTLINES_PARENT => $root
    ]
]);

// let's remember the item (id)
$parent = $merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Fantastic-Speaker.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Fantastic Speaker',
        Merger::OUTLINES_PARENT => $root
    ]
]);

// now add this item below the previous one
$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Noisy-Tube.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Noisy Tube',
        Merger::OUTLINES_PARENT => $parent
    ]
]);

// merger
$merger->merge();

$document->save()->finish();
