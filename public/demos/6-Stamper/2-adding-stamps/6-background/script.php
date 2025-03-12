<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Pdf as PdfStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('background.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

// initiate the stamp - we use a PDF page as the background
$stamp = new PdfStamp($assetsDirectory . '/pdfs/crumpled-paper.pdf');

// add stamp to the stamper
$stamper->addStamp($stamp, [
    'underlay' => true,
    // we use a callback to adjust the stamp size to the page size
    'callback' => static function(
        int $pageNumber,
        int $pageCount,
        Page $page,
        PdfStamp $stamp
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
