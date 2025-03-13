<?php

use setasign\SetaPDF2\Core\Canvas\Draw;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\Stamper\Stamp\XObjectStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('smile.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

// create a XObject
$xObject = Form::create($document, array(0, 0, 205, 205));
// get the Canvas
$canvas = $xObject->getCanvas();
// Let's draw a smiley ;-)
$canvas
    ->setStrokingColor('#FF0000')
    ->setNonStrokingColor('#FFFF00')
    ->rotate(102.5, 102.5, 15)
    ->path()
    ->setLineWidth(5)
    ->draw()
    ->circle(102.5, 102.5, 100, Draw::STYLE_DRAW_AND_FILL) // head
    ->circle(60, 120, 15) // left eye
    ->circle(140, 120, 15) // right exe
    ->path()
    ->moveTo(50, 60)
    ->curveTo(60, 20, 145, 20, 155, 60) // mouth
    ->stroke();

// create the stamp object for the XObject
$xObjectStamp = new XObjectStamp($xObject);
$xObjectStamp->setOpacity(.5);

$stamper->addStamp($xObjectStamp, Stamper::POSITION_CENTER_MIDDLE);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
