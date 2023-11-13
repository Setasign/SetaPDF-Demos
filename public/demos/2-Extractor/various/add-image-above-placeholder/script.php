<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report - with-sig-placeholders.pdf'
);

// initate an extractor instance
$extractor = new \SetaPDF_Extractor($document);

// define the word strategy
$strategy = new \SetaPDF_Extractor_Strategy_Word();
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// define a mapping of placeholders to images
$images = [
    'SIGNATURE' => \SetaPDF_Core_Image::getByPath(
        $assetsDirectory . '/images/Handwritten-Signature.png'
    )->toXObject($document)
];

// let's find all placeholders
$matches = [];
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var \SetaPDF_Extractor_Result_Words $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);
    $matches[] = [$pageNo, $words->search('/{{.*?}}/')];
}

// iterate over the matches
foreach ($matches AS list($pageNo, $results)) {
    /** @var \SetaPDF_Extractor_Result_Words $segments */
    foreach ($results as $segments) {
        $name = '';
        foreach ($segments AS $segment) {
            $name .= $segment->getString();
        }

        $name = trim($name, '{}');

        if (!isset($images[$name])) {
            continue;
        }

        // get the bounds of the found phrase
        $bounds = $segments->getBounds();
        $rect = $bounds[0]->getRectangle();

        // get the page object
        $page = $pages->getPage($pageNo);
        // make sure that the new content is encapsulated in a seperate content stream
        $page->getContents()->encapsulateExistingContentInGraphicState();
        // get the canvas object
        $canvas = $page->getCanvas();

        // get some rect data
        $x = $rect->getLl()->getX();
        $y = $rect->getLl()->getY();
        $width = $rect->getWidth();
        $height = $rect->getHeight();

        // draw a white rectangle
        $canvas->draw()
            ->setNonStrokingColor(1)
            ->rect($x, $y, $width, $height, \SetaPDF_Core_Canvas_Draw::STYLE_FILL);

        /**
         * @var \SetaPDF_Core_XObject_Image $image
         */
        $image = $images[$name];

        // draw the image fitted and centered to the placeholder area
        $maxWidth = $image->getWidth($height);
        $maxHeight = $image->getHeight($width);

        if ($maxHeight > $height) {
            $x += $width / 2 - $maxWidth / 2;
            $image->draw($canvas, $x, $y, null, $height);
        } else {
            $y += $height / 2 - $maxHeight / 2;
            $image->draw($canvas, $x, $y, $width, null);
        }
    }
}

// save and finish the document
$document->setWriter(new \SetaPDF_Core_Writer_Http('document.pdf', true));
$document->save()->finish();
