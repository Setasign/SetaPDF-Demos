<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a writer
$writer = new SetaPDF_Core_Writer_Http('callback.pdf', true);
// we create a fresh document instance for demonstration purpose
$document = new SetaPDF_Core_Document($writer);
// get the pages instance
$pages = $document->getCatalog()->getPages();
// create 20 pages
for ($i = 0; $i < 20; $i++) {
    $orientation = $i % 2
        ? SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE
        : SetaPDF_Core_PageFormats::ORIENTATION_PORTRAIT;

    $pages->create(SetaPDF_Core_PageFormats::A4, $orientation);
}

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a font object
$font = SetaPDF_Core_Font_Standard_Helvetica::create($document);

// create simple text stamp
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 16);
$stamp->setPadding(4);
$stamp->setAlign(SetaPDF_Core_Text::ALIGN_RIGHT);

/**
 * @param $pageNumber The current page number that should be stamped
 * @param $pageCount The page count of the document
 * @param SetaPDF_Core_Document_Page $page The page instance of the current page
 * @param SetaPDF_Stamper_Stamp_Text $stamp The stamp object
 * @param array $currentStampData The data that were passed to the addStamp() method.
 *
 * @return bool
 */
$callback = function(
    $pageNumber,
    $pageCount,
    SetaPDF_Core_Document_Page $page,
    SetaPDF_Stamper_Stamp_Text $stamp,
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
        $currentStampData['position'] = SetaPDF_Stamper::POSITION_LEFT_BOTTOM;
        $currentStampData['rotation'] = 45;
    // otherwise lower right and a -45 degree rotation
    } else {
        $currentStampData['position'] = SetaPDF_Stamper::POSITION_RIGHT_BOTTOM;
        $currentStampData['rotation'] = -45;
    }

    return true;
};

// right bottom and callback
$stamper->addStamp($stamp, [
    'position' => SetaPDF_Stamper::POSITION_RIGHT_BOTTOM,
    'callback' => $callback
]);

// stamp the document
$stamper->stamp();

// show the whole page at opening time
$document->getCatalog()->setPageLayout(SetaPDF_Core_Document_PageLayout::SINGLE_PAGE);

// save and send it to the client
$document->save()->finish();
