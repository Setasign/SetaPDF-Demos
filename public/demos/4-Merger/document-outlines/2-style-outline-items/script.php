<?php

use setasign\SetaPDF2\Core\Document\PageMode;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new Merger();

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Boombastic-Box.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Boombastic Box',
        Merger::OUTLINES_BOLD => true
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Fantastic-Speaker.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Fantastic Speaker',
        Merger::OUTLINES_ITALIC => true
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/etown/products/Noisy-Tube.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Noisy Tube',
        Merger::OUTLINES_ITALIC => true,
        Merger::OUTLINES_BOLD => true,
        Merger::OUTLINES_COLOR => [1, 0, 0] // RGB
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(PageMode::USE_OUTLINES);

$document->setWriter(new HttpWriter('styled-bookmark-outline.pdf', true));
$document->save()->finish();
