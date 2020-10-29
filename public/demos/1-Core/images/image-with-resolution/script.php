<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/images/*/*.{png,jpg,jpeg,gif}', GLOB_BRACE);

$imgPath = displayFiles($files, true, ['dpi' => [72, 96, 150, 300]]);

// create a writer
$writer = new SetaPDF_Core_Writer_Http('ImgInSpecificResolution.pdf', true);
// create a document
$document = new SetaPDF_Core_Document($writer);

$img = SetaPDF_Core_Image::getByPath($imgPath);
$xObject = $img->toXObject($document);
$width = $xObject->getWidth();
$height = $xObject->getHeight();

// calculate the width by the given DPI value
$dpi = isset($_GET['dpi']) ? abs($_GET['dpi']) : 72;
$dpi = $dpi == 0 ? 72 : $dpi;

$width = $width * 72 / $dpi;
$height = $height * 72 / $dpi;

$pages = $document->getCatalog()->getPages();
$page = $pages->create(
    [$width, $height],
    SetaPDF_Core_PageFormats::ORIENTATION_AUTO
);
$canvas = $page->getCanvas();
$xObject->draw($canvas, 0, 0, $width, $height);

$document->save()->finish();