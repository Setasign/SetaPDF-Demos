<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// create a writer instance
$writer = new SetaPDF_Core_Writer_Http('transparency.pdf', true);

// create a document instance
$document = new SetaPDF_Core_Document($writer);

$pages = $document->getCatalog()->getPages();
$page = $pages->create(SetaPDF_Core_PageFormats::A4);

$image = SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/fuchslocher/green-morning.jpg')->toXObject($document);

$canvas = $page->getCanvas();

$image->draw($canvas, 30, 600, 250);

// create a graphic state with opacity set to 0.7
$gs = new SetaPDF_Core_Resource_ExtGState();
$gs->setConstantOpacity(.7);
$gs->setConstantOpacityNonStroking(.7);
$gs->getIndirectObject($document);

// and draw the image
$canvas->saveGraphicState();
$canvas->setGraphicState($gs);
$image->draw($canvas, 60, 570, 250);
$canvas->restoreGraphicState();

// create a graphic state with opacity set to 0.4
$gs = new SetaPDF_Core_Resource_ExtGState();
$gs->setConstantOpacity(.4);
$gs->setConstantOpacityNonStroking(.4);
$gs->getIndirectObject($document);

// and draw the image
$canvas->saveGraphicState();
$canvas->setGraphicState($gs);
$image->draw($canvas, 90, 540, 250);
$canvas->restoreGraphicState();

// create a graphic state with opacity set to 0.1
$gs = new SetaPDF_Core_Resource_ExtGState();
$gs->setConstantOpacity(.1);
$gs->setConstantOpacityNonStroking(.1);
$gs->getIndirectObject($document);

// and draw the image
$canvas->saveGraphicState();
$canvas->setGraphicState($gs);
$image->draw($canvas, 120, 510, 250);
$canvas->restoreGraphicState();

// save and finish
$document->save()->finish();
