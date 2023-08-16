<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a writer
$writer = new \SetaPDF_Core_Writer_Http('background.pdf', true);
// get a document instance
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    $writer
);

// create a stamper instance
$stamper = new \SetaPDF_Stamper($document);

// initiate the stamp - we use a PDF page as the background
$stamp = new \SetaPDF_Stamper_Stamp_Pdf($assetsDirectory . '/pdfs/crumpled-paper.pdf');

// add stamp to the stamper
$stamper->addStamp($stamp, [
    'underlay' => true,
    // we use a callback to adjust the stamp size to the page size
    'callback' => static function(
        $pageNumber,
        $pageCount,
        \SetaPDF_Core_Document_Page $page,
        \SetaPDF_Stamper_Stamp_Pdf $stamp
    ) {
        $stamp->setWidth($page->getWidth());
        $stamp->setHeight($page->getHeight());
        return true;
    }
]);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
