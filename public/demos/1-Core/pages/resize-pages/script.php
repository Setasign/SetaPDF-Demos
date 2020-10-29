<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

$path = displayFiles($files);

// create a writer instance
$writer = new SetaPDF_Core_Writer_Http('resize-pages.pdf', true);
// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// iterate the document page by page and get some properties
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {

    // get the page object
    $page = $pages->getPage($pageNo);

    // resize all available page boxes
    foreach (SetaPDF_Core_PageBoundaries::$all AS $boxName) {
        $box = $page->getBoundary($boxName, false);
        if ($box === false) {
            continue;
        }

        $box->setLlx($box->getLlx() - 100);
        $box->setLly($box->getLly() - 100);
        $box->setUrx($box->getUrx() + 100);
        $box->setUry($box->getUry() + 100);

        // reset the box
        $page->setBoundary($box, $boxName);
    }
}

$document->save()->finish();
