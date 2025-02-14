<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image;
use setasign\SetaPDF2\Core\Image\Jpeg;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/*/Logo.*');

// let's define a DPI value for the images
$dpi = 150;

$merger = new Merger();

foreach ($files as $path) {
    // simple check for an image file
    $image = getimagesize($path);
    if ($image !== false) {
        // now create an empty document instance
        $imageDocument = new Document();
        // load the image
        $image = Image::getByPath($path);
        // convert it into an XObject
        $xObject = $image->toXObject($imageDocument);

        // calculate the size in view to the given resolution
        $width = $xObject->getWidth() * 72 / $dpi;
        $height = $xObject->getHeight() * 72 / $dpi;

        // create a page
        $pages = $imageDocument->getCatalog()->getPages();
        $page = $pages->create(
            [$width, $height],
            PageFormats::ORIENTATION_AUTO
        );

        // draw the image onto the page
        $canvas = $page->getCanvas();
        $xObject->draw($canvas, 0, 0, $width, $height);

        // JPEG images could be rotated by flags in their EXIF headers. To support these flags,
        // simply rotate the page accordingly:
        if ($image instanceof Jpeg && function_exists('exif_read_data')) {
            $exifData = exif_read_data($path);
            if (isset($exifData['Orientation'])) {
                switch ($exifData['Orientation']) {
                    case 3:
                    case 4:
                        $page->setRotation(180);
                        break;
                    case 5:
                    case 6:
                        $page->setRotation(90);
                        break;
                    case 7:
                    case 8:
                        $page->setRotation(270);
                        break;
                }
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

$document->setWriter(new HttpWriter('PDFs-and-Images.pdf', true));
$document->save()->finish();
