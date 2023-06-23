<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/*/eBook-Invoice.pdf');

$path = displayFiles($files);

$document = \SetaPDF_Core_Document::loadByFilename($path);

// initiate an extractor instance
$extractor = new SetaPDF_Extractor($document);

// create a word strategy
$strategy = new SetaPDF_Extractor_Strategy_Word();

// define filter areas
$invoicingPartyFilter = new SetaPDF_Extractor_Filter_Rectangle(
    new \SetaPDF_Core_Geometry_Rectangle(40, 705, 220, 720),
    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT,
    'invoicingParty'
);

// define filter areas
$invoiceNoFilter = new SetaPDF_Extractor_Filter_Rectangle(
    new \SetaPDF_Core_Geometry_Rectangle(512, 520, 580, 540),
    SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT,
    'invoiceNo'
);

// pass them to the strategy
$strategy->setFilter(new SetaPDF_Extractor_Filter_Multi([$invoicingPartyFilter, $invoiceNoFilter]));

// set the strategy
$extractor->setStrategy($strategy);

// get the result
/** @var SetaPDF_Extractor_Result_Words $words */
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
/** @var SetaPDF_Extractor_Result_Word $word */
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

$document->setWriter(new \SetaPDF_Core_Writer_Http('document.pdf', true));
$document->save()->finish();
