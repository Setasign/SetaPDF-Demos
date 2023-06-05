<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new SetaPDF_Core_Writer_Http('pdf-form-stamp.pdf', true);
// get a document instance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Order-Form-without-Signaturefield.pdf',
    $writer
);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// get an image instance
$image = SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/Handwritten-Signature.png');
// initiate the stamp
$stamp = new SetaPDF_Stamper_Stamp_Image($image);
// set height (and width until no setWidth is set the ratio will retain)
$stamp->setHeight(40);

// add stamp to stamper on position right bottom for page 1 with a specific translation
$stamper->addStamp($stamp, [
    'position' => SetaPDF_Stamper::POSITION_RIGHT_BOTTOM,
    'showOnPage' => 1,
    'translateX' => -130,
    'translateY' => 60
]);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
