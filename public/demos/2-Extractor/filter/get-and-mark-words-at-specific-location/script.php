<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\MultiFilter;
use setasign\SetaPDF2\Extractor\Filter\RectangleFilter;
use setasign\SetaPDF2\Extractor\Result\Word;
use setasign\SetaPDF2\Extractor\Result\Words;
use setasign\SetaPDF2\Extractor\Strategy\WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/*/eBook-Invoice.pdf');

$path = displayFiles($files);

$document = Document::loadByFilename($path);

// initiate an extractor instance
$extractor = new Extractor($document);

// create a word strategy
$strategy = new WordStrategy();

// define filter areas
$invoicingPartyFilter = new RectangleFilter(
    new Rectangle(40, 705, 220, 720),
    RectangleFilter::MODE_CONTACT,
    'invoicingParty'
);

// define filter areas
$invoiceNoFilter = new RectangleFilter(
    new Rectangle(512, 520, 580, 540),
    RectangleFilter::MODE_CONTACT,
    'invoiceNo'
);

// pass them to the strategy
$strategy->setFilter(new MultiFilter([$invoicingPartyFilter, $invoiceNoFilter]));

// set the strategy
$extractor->setStrategy($strategy);

// get the result
/** @var Words $words */
$words = $extractor->getResultByPageNumber(1);

// mark the filter areas and words on the pages canvas
$canvas = $document->getCatalog()->getPages()->getPage(1)->getCanvas();

// draw the filter rectangles
$rect = $invoiceNoFilter->getRectangle();
$canvas
    ->setStrokingColor([1, 0, 1])
    ->draw()->rect($rect->getLl()->getX(), $rect->getLl()->getY(), $rect->getWidth(), $rect->getHeight());
$rect = $invoicingPartyFilter->getRectangle();
$canvas
    ->setStrokingColor([1, 0, 1])
    ->draw()->rect($rect->getLl()->getX(), $rect->getLl()->getY(), $rect->getWidth(), $rect->getHeight());

// draw the word boundaries
/** @var Word $word */
foreach ($words AS $word) {
    // to get access to the filter id which was used to resolve this word, just use:
    // $filterId = $word->getFilterId();

    foreach ($word->getBounds() AS $boundary) {
        $canvas
            ->setStrokingColor([0, 1, 0])
            ->draw()->rect(
                $boundary->getLl()->getX(),
                $boundary->getLl()->getY(),
                $boundary->getUr()->getX() - $boundary->getLl()->getX(),
                $boundary->getUr()->getY() -  $boundary->getLl()->getY()
            );
    }
}

$document->setWriter(new HttpWriter('document.pdf', true));
$document->save()->finish();
