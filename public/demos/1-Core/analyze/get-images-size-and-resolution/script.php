<?php

use com\setasign\SetaPDF\Demos\ContentStreamProcessor\ImageProcessor;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/pdfs/lenstown/products/*.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';
$files[] = $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf';
$files[] = $assetsDirectory . '/pdfs/misc/Handwritten-Signature.pdf';

$path = displayFiles($files);

require_once $classesDirectory . '/ContentStreamProcessor/ImageProcessor.php';

// display the information about the found images
if (!isset($_GET['p'])) {

    // load a document instance
    $document = SetaPDF_Core_Document::loadByFilename($path);
    // get access to the pages object
    $pages = $document->getCatalog()->getPages();

    // walk through the pages
    for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
        $page = $pages->getPage($pageNo);

        // process the canvas of the page
        $imageProcessor = new ImageProcessor($page->getCanvas(), ($page->getRotation() / 90) % 2 > 0);
        $images = $imageProcessor->process();

        echo '<pre>';
        if (count($images)) {
            echo '<a href="?f=' . urlencode($_GET['f']) . '&p=' . $pageNo . '#page=' . $pageNo . '">Found '
                . count($images) . ' images on page #' . $pageNo . "</a>.\n";
        } else {
            echo 'Found no images on page #' . $pageNo . ".\n";
        }
        foreach ($images as $no => $image) {
            echo '  Image #' . ($no + 1) . "\n";
            echo '    ll => ' . $image['ll']->getX() . ' / ' . $image['ll']->getY() . "\n";
            echo '    ul => ' . $image['ul']->getX() . ' / ' . $image['ul']->getY() . "\n";
            echo '    ur => ' . $image['ur']->getX() . ' / ' . $image['ur']->getY() . "\n";
            echo '    lr => ' . $image['lr']->getX() . ' / ' . $image['lr']->getY() . "\n";
            echo '    width => ' . $image['width'] . "\n";
            echo '    height => ' . $image['height'] . "\n";
            echo '    resolutionX => ' . $image['resolutionX'] . "\n";
            echo '    resolutionY => ' . $image['resolutionY'] . "\n";
            echo '    pixelWidth => ' . $image['pixelWidth'] . "\n";
            echo '    pixelHeight => ' . $image['pixelHeight'] . "\n";
            echo "\n";
        }
        echo '</pre>';
        echo '<br/>';
    }

// mark the images of a specific page and output the resulting PDF
} else {

    // let's create a writer and document instance
    $writer = new SetaPDF_Core_Writer_Http('marked.pdf', true);
    $document = SetaPDF_Core_Document::loadByFilename($path, $writer);

    // get access to the pages object
    $pages = $document->getCatalog()->getPages();

    // get the page by the given parameter
    $page = $pages->getPage($_GET['p']);

    // set an open action, so that the page is shown when opened (requires support of the reader application)
    $document->getCatalog()->setOpenAction(SetaPDF_Core_Document_Destination::createByPage($page));

    // get access to the pages canvas
    $canvas = $page->getCanvas();

    // let's get the image information
    $imageProcessor = new ImageProcessor($canvas, ($page->getRotation() / 90) % 2 > 0);
    $images = $imageProcessor->process();

    // ensure a fresh graphic state
    $page->getContents()->encapsulateExistingContentInGraphicState();

    // draw rectangles around the found images
    $canvas->saveGraphicState();
    $canvas->path()->setLineWidth(2);
    $canvas->setStrokingColor('#ff00ff');
    foreach ($images as $image) {
        $canvas->draw()
            ->rect($image['ll']->getX(), $image['ll']->getY(), $image['width'], $image['height']);
    }
    $canvas->restoreGraphicState();

    // send the document to the client
    $document->save()->finish();
}
