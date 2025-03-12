<?php

use setasign\SetaPDF2\Core\Document\PageMode;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new Merger();

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
        Merger::OUTLINES_TITLE => 'Noisy Tube'
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(PageMode::USE_OUTLINES);

$document->setWriter(new HttpWriter('simple-bookmark-outline.pdf', true));
$document->save()->finish();
