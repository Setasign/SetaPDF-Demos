<?php

use setasign\SetaPDF2\Core\Document\PageMode;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new Merger();

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_COPY => Merger::COPY_OUTLINES_TO_ROOT
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_COPY => Merger::COPY_OUTLINES_TO_ROOT
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(PageMode::USE_OUTLINES);

$document->setWriter(new HttpWriter('outlines-in-root.pdf', true));
$document->save()->finish();
