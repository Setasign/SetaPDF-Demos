<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$writer = new SetaPDF_Core_Writer_Http('link.pdf', true);
$document = new SetaPDF_Core_Document($writer);

// get access to the pages
$pages = $document->getCatalog()->getPages();
// create a new page
$page = $pages->create(SetaPDF_Core_PageFormats::A4);
// access its canvas
$canvas = $page->getCanvas();

// create a font instance
$font = SetaPDF_Core_Font_Standard_Helvetica::create($document);

// use the text block helper to draw the text
$text = new SetaPDF_Core_Text_Block($font, 12);
$text->setText('This is a link to www.setasign.com!');
$x = $page->getWidth() / 2 - $text->getWidth() / 2;
$y = $page->getHeight() - 100;
$text->draw($canvas, $x, $y);

// create the link annotation
$link = new SetaPDF_Core_Document_Page_Annotation_Link(
    [$x, $y, $x + $text->getWidth(), $y + $text->getHeight()],
    new SetaPDF_Core_Document_Action_Uri('https://www.setasign.com')
);
// and add it
$page->getAnnotations()->add($link);

$document->save()->finish();

