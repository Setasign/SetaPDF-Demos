<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\UriAction;
use setasign\SetaPDF2\Core\Document\Page\Annotation\LinkAnnotation;
use setasign\SetaPDF2\Core\Font\Standard\Helvetica;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Text\Block;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$writer = new HttpWriter('link.pdf', true);
$document = new Document($writer);

// get access to the pages
$pages = $document->getCatalog()->getPages();
// create a new page
$page = $pages->create(PageFormats::A4);
// access its canvas
$canvas = $page->getCanvas();

// create a font instance
$font = Helvetica::create($document);

// use the text block helper to draw the text
$text = new Block($font, 12);
$text->setText('This is a link to www.setasign.com!');
$x = $page->getWidth() / 2 - $text->getWidth() / 2;
$y = $page->getHeight() - 100;
$text->draw($canvas, $x, $y);

// create the link annotation
$link = new LinkAnnotation(
    [$x, $y, $x + $text->getWidth(), $y + $text->getHeight()],
    new UriAction('https://www.setasign.com')
);
// and add it
$page->getAnnotations()->add($link);

$document->save()->finish();

