<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a file writer
$writer = new SetaPDF_Core_Writer_Http('page-numbering-demo.pdf', true);
// load document by filename
$document = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/lenstown/products/All.pdf', $writer);

// create a stamper instance for the document
$stamper = new SetaPDF_Stamper($document);

// let's use a TrueType font for the stamp appearance:
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans-ExtraLight.ttf'
);

// create a stamp with the created font and fontsize of 10 points
$stamp = new SetaPDF_Stamper_Stamp_Text($font, 10);
$stamp->setTextColor('#6d6f72');

// let's create links back to page 1
$dest = SetaPDF_Core_Document_Destination::createByPageNo($document, 1);
$action = new SetaPDF_Core_Document_Action_GoTo($dest);
$stamp->setAction($action);

// a callback to set the pagenumbers for each page
function callbackForPageNumbering($pageNumber, $pageCount, $page, SetaPDF_Stamper_Stamp_Text $stamp) {
    // set the text for the stamp object before stamping
    $stamp->setText("Page $pageNumber of $pageCount");

    // if the callback don't return true the page won't be stamped
    return true;
}

// add the stamp and assign the callback function
$stamper->addStamp($stamp, [
    'position' => SetaPDF_Stamper::POSITION_RIGHT_BOTTOM,
    'translateX' => -29,
    'translateY' => 30,
    'callback' => 'callbackForPageNumbering'
]);

// In the next step we create an xobject stamp object which we use to draw a
// simple line above the page numbering. We initate it with a temporary width
// of 10 points and stretch it a runtime depending on the page width.

$lineWidth = .7;

// create a XObject
$xObject = SetaPDF_Core_XObject_Form::create($document, [0, 0, 10, $lineWidth]);
// get the Canvas
$canvas = $xObject->getCanvas();
// set the collor and draw a line
$canvas
    ->setColor('#39b54b')
    ->path()
    ->setLineWidth($lineWidth)
    ->draw()
    ->line(0, $lineWidth / 2, 10, $lineWidth / 2);

// create the stamp object for the XObject
$xObjectStamp = new SetaPDF_Stamper_Stamp_XObject($xObject);
$xObjectStamp->setHeight($lineWidth);

// add the line 4 points above the page numbering text and ensure the correct width through a callback
$stamper->addStamp($xObjectStamp, [
    'position' => SetaPDF_Stamper::POSITION_RIGHT_BOTTOM,
    'translateX' => -29, // relative to right bottom
    'translateY' => 30 + $stamp->getHeight() + 4,
    'callback' => static function(
        $pageNumber,
        $pageCount,
        SetaPDF_Core_Document_Page $page,
        SetaPDF_Stamper_Stamp_XObject $stamp
    ) {
        $stamp->setWidth($page->getWidth() - 29 * 2);
        return true;
    }
]);

// stamp the document with all added stamps of the stamper
$stamper->stamp();
// save the file and finish the writer (e.g. file handler will closed)
$document->save()->finish();
