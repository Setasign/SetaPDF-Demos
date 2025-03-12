<?php

use setasign\SetaPDF2\Core\Canvas\Draw;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Text\Block;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Words;
use setasign\SetaPDF2\Extractor\Strategy\Word as WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf'
);

// initiate an extractor instance
$extractor = new Extractor($document);

// define the word strategy
$strategy = new WordStrategy();
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// let's find all placeholders
$matches = [];
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var Words $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);
    // we search for the number "11563"
    $matches[] = [$pageNo, $words->search('/11563/')];
}

if (count($matches)) {
    $font = new Subset(
        $document,
        $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
    );
}

// iterate over the matches
foreach ($matches AS [$pageNo, $results]) {
    /** @var Words $segments */
    foreach ($results as $segments) {
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

        $textBlock = new Block($font, $height * .7);
        $textBlock->setText('875631');
        $textBlock->setAlign(Text::ALIGN_CENTER);
        $textBlock->setBorderColor([1, 0, 0]);
        $textBlock->setBorderWidth(1);
        $textBlock->draw($canvas, $x, $y);
    }
}

// save and finish the document
$document->setWriter(new HttpWriter('document.pdf', true));
$document->save()->finish();
