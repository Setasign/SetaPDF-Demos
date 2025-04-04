<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    [
        'path' => $assetsDirectory . '/pdfs/camtown/Logo.png',
        'displayValue' => 'camtown/Logo.png',
    ],
    [
        'path' => $assetsDirectory . '/pdfs/etown/Logo.png',
        'displayValue' => 'etown/Logo.png',
    ],
    [
        'path' => $assetsDirectory . '/pdfs/lenstown/Logo.png',
        'displayValue' => 'lenstown/Logo.png',
    ],
    [
        'path' => $assetsDirectory . '/pdfs/tektown/Logo.png',
        'displayValue' => 'tektown/Logo.png',
    ]
];

$imgPath = displayFiles($files)['path'];

$writer = new HttpWriter('result.pdf', true);
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-without-personalization.pdf',
    $writer
);

// get access to the pages object
$pages = $document->getCatalog()->getPages();

// get the first page
$pageOne = $pages->getPage(1);

// make sure that we have a clean graphic state
$pageOne->getContents()->encapsulateExistingContentInGraphicState();

// get the canvas
$canvas = $pageOne->getCanvas();

// normalize the rotation of the page, so that the origin is at the lower left throughout
$canvas->normalizeRotationAndOrigin($pageOne->getRotation(), $pageOne->getBoundary());

// create an image instance
$image = Image::getByPath($imgPath)->toXObject($document);

// let's use a fixed height
$height = 40;

// draw it onto the canvas
$image->draw($canvas, 30, $pageOne->getHeight() - $height - 30, null, $height);

// save and finish
$document->save()->finish();
