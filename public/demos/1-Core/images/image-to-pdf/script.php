<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/images/*/*.{png,jpg,jpeg,gif}', GLOB_BRACE);

$imgPath = displayFiles($files);

// create a writer
$writer = new SetaPDF_Core_Writer_Http('ImgToPdf.pdf', true);
// create a document
$document = new SetaPDF_Core_Document($writer);

$img = SetaPDF_Core_Image::getByPath($imgPath);
$xObject = $img->toXObject($document);

$pages = $document->getCatalog()->getPages();
$page = $pages->create(
    [$xObject->getWidth(), $xObject->getHeight()],
    SetaPDF_Core_PageFormats::ORIENTATION_AUTO
);

$canvas = $page->getCanvas();
$xObject->draw($canvas);

$document->save()->finish();