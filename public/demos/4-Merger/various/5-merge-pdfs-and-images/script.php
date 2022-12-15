<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/*/Logo.*');

// let's define a DPI value for the images
$dpi = 150;

$merger = new SetaPDF_Merger();

foreach ($files as $path) {
    // simple check for an image file
    $image = getimagesize($path);
    if ($image !== false) {
        // now create an empty document instance
        $imageDocument = new SetaPDF_Core_Document();
        // load the image
        $imgage = SetaPDF_Core_Image::getByPath($path);
        // convert it into an XObject
        $xObject = $imgage->toXObject($imageDocument);

        // calculate the size in view to the given resolution
        $width = $xObject->getWidth() * 72 / $dpi;
        $height = $xObject->getHeight() * 72 / $dpi;

        // create a page
        $pages = $imageDocument->getCatalog()->getPages();
        $page = $pages->create(
            [$width, $height],
            SetaPDF_Core_PageFormats::ORIENTATION_AUTO
        );

        // draw the image onto the page
        $canvas = $page->getCanvas();
        $xObject->draw($canvas, 0, 0, $width, $height);

        // JPEG images could be rotated by flags in their EXIF headers. To support these flags,
        // simply rotate the page accordingly:
        if ($imgage instanceof SetaPDF_Core_Image_Jpeg && function_exists('exif_read_data')) {
            $exifData = exif_read_data($path);
            switch ($exifData['Orientation']) {
                case 3:
                case 4:
                    $page->setRotation(180);
                case 5:
                case 6:
                    $page->setRotation(90);
                case 7:
                case 8:
                    $page->setRotation(270);
            }
        }

        // add the document instance to the merger instance
        $merger->addDocument($imageDocument);
    } else {
        // a simple PDF document
        $merger->addFile($path);
    }
}

$merger->merge();

$document = $merger->getDocument();

$document->setWriter(new SetaPDF_Core_Writer_Http('PDFs-and-Images.pdf', true));
$document->save()->finish();
