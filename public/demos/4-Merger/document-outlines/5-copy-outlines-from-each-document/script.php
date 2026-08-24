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
        Merger::OUTLINES_TITLE => 'Brand-Guide.pdf',
        Merger::OUTLINES_COPY => Merger::COPY_OUTLINES_AS_CHILDS
    ]
]);

$merger->addFile([
    'filename' => $assetsDirectory . '/pdfs/Fuchslocher-Example.pdf',
    'outlinesConfig' => [
        Merger::OUTLINES_TITLE => 'Fuchslocher-Example.pdf',
        Merger::OUTLINES_COPY => Merger::COPY_OUTLINES_AS_CHILDS
    ]
]);

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// show outlines when document opens
$document->getCatalog()->setPageMode(PageMode::USE_OUTLINES);

// we're also going to close the items in the root node
$iterator = $document->getCatalog()->getOutlines()->getIterator();
$iterator->setMaxDepth(0);
foreach ($iterator as $item) {
    $item->close();
}

$document->setWriter(new HttpWriter('outlines-as-childs.pdf', true));
$document->save()->finish();
