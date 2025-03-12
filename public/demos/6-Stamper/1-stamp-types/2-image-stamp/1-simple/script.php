<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Image as ImageStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('image-stamp.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-without-personalization.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

// get an image instance
$image = Image::getByPath($assetsDirectory . '/pdfs/camtown/Logo.png');
// initiate the stamp
$stamp = new ImageStamp($image);
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
