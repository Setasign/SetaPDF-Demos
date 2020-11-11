<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report - with-sig-placeholders.pdf'
);

// initate an extractor instance
$extractor = new SetaPDF_Extractor($document);

// define the word strategy
$strategy = new SetaPDF_Extractor_Strategy_Word();
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// define a mapping of placeholders to images
$images = [
    'SIGNATURE' => SetaPDF_Core_Image::getByPath(
        $assetsDirectory . '/images/Handwritten-Signature.png'
    )->toXObject($document)
];

// let's find all place holders
$matches = [];
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var SetaPDF_Extractor_Result_Word[] $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);

    // let's iterate over all words and search for '{{', followed by a anything and followed by '}}'
    $segments = null;
    foreach ($words AS $word) {
        $string = $word->getString();
        if ($string === '{{') {
            $segments = new SetaPDF_Extractor_Result_Collection([$word]);
            continue;
        }

        if ($segments === null)
            continue;

        $segments[] = $word;

        if ($string === '}}') {
            $matches[] = [$pageNo, $segments];
            $segments = null;
        }
    }
}

// iterate over the matches
foreach ($matches AS $match) {
    /** @var SetaPDF_Extractor_Result_Collection $segments */
    $segments = $match[1];

    $name = '';
    foreach ($segments AS $segment) {
        $name .= $segment->getString();
    }

    $name = trim($name, '{}');

    if (!isset($images[$name])) {
        continue;
    }

    // get the bounds of all 3 words
    $bounds = $segments->getBounds();
    $rect = $bounds[0]->getRectangle();

    // get the page object
    $page = $pages->getPage($match[0]);
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
        ->rect($x, $y, $width, $height, SetaPDF_Core_Canvas_Draw::STYLE_FILL);

    /**
     * @var SetaPDF_Core_XObject_Image $image
     */
    $image = $images[$name];
    // draw the image onto the canvas
    $image->draw($canvas, $x, $y, $width, $height);
}

// save and finish the document
$document->setWriter(new SetaPDF_Core_Writer_Http('document.pdf', true));
$document->save()->finish();
