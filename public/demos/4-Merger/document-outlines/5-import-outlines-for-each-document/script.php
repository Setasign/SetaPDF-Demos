<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new \SetaPDF_Merger();

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_TITLE => 'Brand-Guide.pdf',
        \SetaPDF_Merger::OUTLINES_COPY => \SetaPDF_Merger::COPY_OUTLINES_AS_CHILDS
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',
    'outlinesConfig' => [
        \SetaPDF_Merger::OUTLINES_TITLE => 'Fuchslocher-Example.pdf',
        \SetaPDF_Merger::OUTLINES_COPY => \SetaPDF_Merger::COPY_OUTLINES_AS_CHILDS
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(\SetaPDF_Core_Document_PageMode::USE_OUTLINES);

// we also going to items in the root node
$iterator = $document->getCatalog()->getOutlines()->getIterator();
$iterator->setMaxDepth(0);
foreach ($iterator as $item) {
    $item->close();
}

$document->setWriter(new \SetaPDF_Core_Writer_Http('oultines-as-childs.pdf', true));
$document->save()->finish();
