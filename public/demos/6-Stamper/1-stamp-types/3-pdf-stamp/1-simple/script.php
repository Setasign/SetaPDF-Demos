<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new SetaPDF_Core_Writer_Http('pdf-stamp.pdf', true);
// get a document instance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-without-personalization.pdf',
    $writer
);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// initiate the stamp
$stamp = new SetaPDF_Stamper_Stamp_Pdf(
    $assetsDirectory . '/pdfs/lenstown/Logo.pdf',
    1,
    SetaPDF_Core_PageBoundaries::ART_BOX
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
