<?php

use setasign\SetaPDF2\Core\Canvas\Draw;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\XObject\Image as ImageXObject;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Words;
use setasign\SetaPDF2\Extractor\Strategy\WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report - with-sig-placeholders.pdf'
);

// initate an extractor instance
$extractor = new Extractor($document);

// define the word strategy
$strategy = new WordStrategy();
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// define a mapping of placeholders to images
$images = [
    'SIGNATURE' => Image::getByPath(
        $assetsDirectory . '/images/Handwritten-Signature.png'
    )->toXObject($document)
];

// let's find all placeholders
$matches = [];
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var Words $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);
    $matches[] = [$pageNo, $words->search('/{{.*?}}/')];
}

// iterate over the matches
foreach ($matches AS list($pageNo, $results)) {
    /** @var Words $segments */
    foreach ($results as $segments) {
        $name = trim($segments->getString(), "{} \n");
        if (!isset($images[$name])) {
            continue;
        }

        // get the bounds of the found phrase
        $bounds = $segments->getBounds();
        $rect = $bounds[0]->getRectangle();

        // get the page object
        $page = $pages->getPage($pageNo);
        // make sure that the new content is encapsulated in a separate content stream
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
            ->rect($x, $y, $width, $height, Draw::STYLE_FILL);

        /**
         * @var ImageXObject $image
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
$document->setWriter(new HttpWriter('document.pdf', true));
$document->save()->finish();
