<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\UriAction;
use setasign\SetaPDF2\Core\Document\OutlinesItem;
use setasign\SetaPDF2\Core\Document\PageMode;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$writer = new HttpWriter('Brand-Guide.pdf', true);
// create a document instance
$document = Document::loadByFilename($assetsDirectory . '/pdfs/Brand-Guide.pdf', $writer);

// get the outlines helper
$outlines = $document->getCatalog()->getOutlines();

// create an item instance
$item = OutlinesItem::create($document, '© Setasign');
// make it bold
$item->setBold(true);

// create an Uri action
$action = new UriAction('https://www.setasign.com');
// add the action to the item
$item->setAction($action);

// add it to the root outline
$outlines->appendChild($item);

// show the outline panel
$document->getCatalog()->setPageMode(PageMode::USE_OUTLINES);

// save and finish the document instance
$document->save()->finish();
