<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\PdfStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('pdf-stamp.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-without-personalization.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

// initiate the stamp
$stamp = new PdfStamp(
    $assetsDirectory . '/pdfs/lenstown/Logo.pdf',
    1,
    PageBoundaries::ART_BOX
);
// set height (and width until no setWidth is set the ratio will retain)
$stamp->setHeight(23);

// add stamp to stamper on position left top for all pages with a specific translation
$stamper->addStamp($stamp, [
    'translateX' => 43,
    'translateY' => -38 // origin is lower left
]);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
