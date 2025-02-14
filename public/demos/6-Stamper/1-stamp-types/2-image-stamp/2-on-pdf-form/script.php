<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Image as ImageStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('pdf-form-stamped.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Order-Form-without-Signaturefield.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

// get an image instance
$image = Image::getByPath($assetsDirectory . '/images/Handwritten-Signature.png');
// initiate the stamp
$stamp = new ImageStamp($image);
// set height (and width until no setWidth is set the ratio will retain)
$stamp->setHeight(40);

// add stamp to stamper on position right bottom for page 1 with a specific translation
$stamper->addStamp($stamp, [
    'position' => Stamper::POSITION_RIGHT_BOTTOM,
    'showOnPage' => 1,
    'translateX' => -130,
    'translateY' => 60
]);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
