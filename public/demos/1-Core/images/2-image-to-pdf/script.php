<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/images/*/*.{png,jpg,jpeg,gif}', GLOB_BRACE);

$imgPath = displayFiles($files);

// create a writer
$writer = new HttpWriter('ImgToPdf.pdf', true);
// create a document
$document = new Document($writer);

$img = Image::getByPath($imgPath);
$xObject = $img->toXObject($document);

$pages = $document->getCatalog()->getPages();
$page = $pages->create(
    [$xObject->getWidth(), $xObject->getHeight()],
    PageFormats::ORIENTATION_AUTO
);

$canvas = $page->getCanvas();
$xObject->draw($canvas);

$document->save()->finish();