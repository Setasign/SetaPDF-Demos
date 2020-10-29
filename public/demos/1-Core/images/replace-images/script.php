<?php

use com\setasign\SetaPDF\Demos\ContentStreamProcessor\ImageProcessor;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/pdfs/lenstown/products/*.pdf');

$path = displayFiles($files);

require_once $classesDirectory . '/ContentStreamProcessor/ImageProcessor.php';

// load a document instance
$document = SetaPDF_Core_Document::loadByFilename($path);
// get access to the pages object
$pages = $document->getCatalog()->getPages();

// define the replacement images
$portraitImage = SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/portrait.jpg');
$portraitXObject = $portraitImage->toXObject($document);
$landscapeImage = SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/landscape.jpg');
$landscapeXObject = $landscapeImage->toXObject($document);

// walk through the pages
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    $page = $pages->getPage($pageNo);

    // create an image processor instance
    $imageProcessor = new ImageProcessor($page->getCanvas(), ($page->getRotation() / 90) % 2 > 0);
    // process the content stream
    $images = $imageProcessor->process();

    foreach ($images AS $image) {
        // we've several information available but for demonstration purpose we just compare
        // the width and height to define which new image will be used
        if ($image['width'] > $image['height']) {
            $image['objectReference']->setValue($landscapeXObject->getIndirectObject());
        } else {
            $image['objectReference']->setValue($portraitXObject->getIndirectObject());
        }
    }
}

// save and finish
$document->setWriter(new SetaPDF_Core_Writer_Http('replaced-images.pdf', true));
$document->save()->finish();

