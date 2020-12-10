<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new SetaPDF_Core_Writer_Http('smile.pdf', true);
// get a document instance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

// create a XObject
$xObject = SetaPDF_Core_XObject_Form::create($document, array(0, 0, 205, 205));
// get the Canvas
$canvas = $xObject->getCanvas();
// Let's draw a smilie ;-)
$canvas
    ->setStrokingColor('#FF0000')
    ->setNonStrokingColor('#FFFF00')
    ->rotate(102.5, 102.5, 15)
    ->path()
    ->setLineWidth(5)
    ->draw()
    ->circle(102.5, 102.5, 100, SetaPDF_Core_Canvas_Draw::STYLE_DRAW_AND_FILL) // head
    ->circle(60, 120, 15) // left eye
    ->circle(140, 120, 15) // right exe
    ->path()
    ->moveTo(50, 60)
    ->curveTo(60, 20, 145, 20, 155, 60) // mouth
    ->stroke();

// create the stamp object for the XObject
$xObjectStamp = new SetaPDF_Stamper_Stamp_XObject($xObject);
$xObjectStamp->setOpacity(.5);

$stamper->addStamp($xObjectStamp, SetaPDF_Stamper::POSITION_CENTER_MIDDLE);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
