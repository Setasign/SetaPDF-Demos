<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page;
use setasign\SetaPDF2\Core\Document\PageLayout;
use setasign\SetaPDF2\Core\Font\Standard\Helvetica;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('callback.pdf', true);
// we create a fresh document instance for demonstration purpose
$document = new Document($writer);
// get the pages instance
$pages = $document->getCatalog()->getPages();
// create 20 pages
for ($i = 0; $i < 20; $i++) {
    $orientation = $i % 2 ? PageFormats::ORIENTATION_LANDSCAPE : PageFormats::ORIENTATION_PORTRAIT;

    $pages->create(PageFormats::A4, $orientation);
}

// create a stamper instance
$stamper = new Stamper($document);

// create a font object
$font = Helvetica::create($document);

// create simple text stamp
$stamp = new TextStamp($font, 16);
$stamp->setPadding(4);
$stamp->setAlign(Text::ALIGN_RIGHT);

/**
 * @param int $pageNumber The current page number that should be stamped
 * @param int $pageCount The page count of the document
 * @param Page $page The page instance of the current page
 * @param TextStamp $stamp The stamp object
 * @param array $currentStampData The data that were passed to the addStamp() method.
 *
 * @return bool
 */
$callback = function(
    int $pageNumber,
    int $pageCount,
    Page $page,
    TextStamp $stamp,
    array &$currentStampData
) {
    // if you return false the stamp will not get drawn
    if ($pageNumber === 2) {
        return false;
    }

    // set the page number
    $stamp->setText('Page ' . $pageNumber . ' / ' . $pageCount);
    // increase the font size for demonstration purpose
    $stamp->setFontSize($stamp->getFontSize() + 2);

    // if the page is landscape we want to position the stamp on the lower left with a rotation of 45 degrees
    if ($page->getWidth() > $page->getHeight()) {
        $currentStampData['position'] = Stamper::POSITION_LEFT_BOTTOM;
        $currentStampData['rotation'] = 45;
    // otherwise lower right and a -45 degrees rotation
    } else {
        $currentStampData['position'] = Stamper::POSITION_RIGHT_BOTTOM;
        $currentStampData['rotation'] = -45;
    }

    return true;
};

// right bottom and callback
$stamper->addStamp($stamp, [
    'position' => Stamper::POSITION_RIGHT_BOTTOM,
    'callback' => $callback
]);

// stamp the document
$stamper->stamp();

// show the whole page at opening time
$document->getCatalog()->setPageLayout(PageLayout::SINGLE_PAGE);

// save and send it to the client
$document->save()->finish();
