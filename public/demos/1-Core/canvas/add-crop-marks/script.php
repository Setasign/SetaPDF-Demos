<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// create a writer instance
$writer = new \SetaPDF_Core_Writer_Http('with-crop-marks.pdf', true);

// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/camtown/Business-Card-filled.pdf', $writer);

$pages = $document->getCatalog()->getPages();

$mm = 2;
$points = $mm * 2.8346456693; // 2mm to Points

// get the page object
$page = $pages->getPage(1);

// let's save the main TrimBox
$trimBox = $page->getTrimBox();

// get the MediaBox and enlarge it
$box = $page->getMediaBox();
$box->setLlx($box->getLlx() - $points);
$box->setLly($box->getLly() - $points);
$box->setUrx($box->getUrx() + $points);
$box->setUry($box->getUry() + $points);

// set the new box as both Media- and CropBox
$page->setMediaBox($box);
$page->setCropBox($box);

// now set a reduced TrimBox
$trimBox->setLlx($trimBox->getLlx() + $points);
$trimBox->setLly($trimBox->getLly() + $points);
$trimBox->setUrx($trimBox->getUrx() - $points);
$trimBox->setUry($trimBox->getUry() - $points);
$page->setTrimBox($trimBox);

// make sure we have a clean graphic state
$page->getStreamProxy()->encapsulateExistingContentInGraphicState();
// get the canvas...
$canvas = $page->getCanvas();

// and draw the crop marks - we use values of both boundary boxes
$canvas->saveGraphicState()
    ->path()
    ->setLineWidth(.1)
    ->setStrokingColor(.3)
    ->draw()
    // left-top
    ->line($box->getLlx(), $trimBox->getUry(), $trimBox->getLlx() - $points / 2, $trimBox->getUry())
    ->line($trimBox->getLlx(), $box->getUry(), $trimBox->getLlx(), $trimBox->getUry() + $points / 2)
    // right-top
    ->line($trimBox->getUrx(), $box->getUry(), $trimBox->getUrx(), $trimBox->getUry() + $points / 2)
    ->line($trimBox->getUrx() + $points / 2, $trimBox->getUry(), $box->getUrx(), $trimBox->getUry())
    // right-bottom
    ->line($box->getUrx(), $trimBox->getLly(), $trimBox->getUrx() + $points / 2, $trimBox->getLly())
    ->line($trimBox->getUrx(), $box->getLly(), $trimBox->getUrx(), $trimBox->getLly() - $points / 2)
    // left-bottom
    ->line($trimBox->getLlx(), $box->getLly(), $trimBox->getLlx(), $trimBox->getLly() - $points / 2)
    ->line($box->getLlx(), $trimBox->getLly(), $trimBox->getLlx() - $points / 2, $trimBox->getLly())
    ->restoreGraphicState();

$document->save()->finish();
