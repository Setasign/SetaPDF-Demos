<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/tektown/invoices/1*.pdf');
$files = array_merge($files, glob($assetsDirectory . '/pdfs/misc/rotated/*.pdf'));
$files = array_merge($files, glob($assetsDirectory . '/pdfs/misc/boxes/*.pdf'));

$paths = displayFiles($files, true, true);

// create a merger instance
$merger = new \SetaPDF_Merger();

// we need some variable to record page numbers and the added file path
$currentPage = 1;
$pagesToFiles = [];
// iterate through paths...
foreach ($paths as $path) {
    $pageCount = $merger->getPageCount($path);
    $pagesToFiles[$currentPage] = [$currentPage + $pageCount, $path];
    $currentPage += $pageCount;

    // ... add them to the merger instance
    $merger->addFile($path);
}

// merge
$merger->merge();

// get access to the document instance
$document = $merger->getDocument();

// we need a font instance
$font = new \SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// access the pages
$pages = $document->getCatalog()->getPages();
// re-iterate through the merged files and pages
foreach ($pagesToFiles as $pageNo => list($nextPage, $path)) {
    for (; $pageNo < $nextPage; $pageNo++) {
        // access the page
        $page = $pages->getPage($pageNo);
        // ensure a clean graphic state
        $page->getStreamProxy()->encapsulateExistingContentInGraphicState();

        // get access to the pages canvas
        $canvas = $page->getCanvas();
        $canvas->saveGraphicState();
        // let's normalize the rotation and the origin
        $canvas->normalizeRotationAndOrigin($page->getRotation(), $page->getBoundary());

        // create a text block
        $textBlock = new \SetaPDF_Core_Text_Block($font, 5);
        $textBlock->setText(basename($path));
        // and draw it onto the canvas
        $textBlock->draw($canvas, $page->getWidth() - $textBlock->getWidth() - 5, 5);

        $canvas->restoreGraphicState();
    }
}

// set a writer instance
$document->setWriter(new \SetaPDF_Core_Writer_Http('merged-with-footnotes.pdf', true));
// and save the result to the writer
$document->save()->finish();
