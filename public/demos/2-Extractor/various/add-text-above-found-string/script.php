<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf'
);

// initate an extractor instance
$extractor = new \SetaPDF_Extractor($document);

// define the word strategy
$strategy = new \SetaPDF_Extractor_Strategy_Word();
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// let's find all placeholders
$matches = [];
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var \SetaPDF_Extractor_Result_Words $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);
    // we search for the number "11563"
    $matches[] = [$pageNo, $words->search('/11563/')];
}

if (count($matches)) {
    $font = new \SetaPDF_Core_Font_TrueType_Subset(
        $document,
        $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
    );
}

// iterate over the matches
foreach ($matches AS list($pageNo, $results)) {
    /** @var \SetaPDF_Extractor_Result_Words $segments */
    foreach ($results as $segments) {
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

        $textBlock = new SetaPDF_Core_Text_Block($font, $height * .7);
        $textBlock->setText('875631');
        $textBlock->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
        $textBlock->setBorderColor([1, 0, 0]);
        $textBlock->setBorderWidth(1);
        $textBlock->draw($canvas, $x, $y);
    }
}

// save and finish the document
$document->setWriter(new \SetaPDF_Core_Writer_Http('document.pdf', true));
$document->save()->finish();
