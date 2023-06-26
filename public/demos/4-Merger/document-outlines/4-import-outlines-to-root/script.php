<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new \SetaPDF_Merger();

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_COPY => \SetaPDF_Merger::COPY_OUTLINES_TO_ROOT
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_COPY => \SetaPDF_Merger::COPY_OUTLINES_TO_ROOT
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(\SetaPDF_Core_Document_PageMode::USE_OUTLINES);

$document->setWriter(new \SetaPDF_Core_Writer_Http('outlines-in-root.pdf', true));
$document->save()->finish();
