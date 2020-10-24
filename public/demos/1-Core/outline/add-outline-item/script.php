<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$writer = new SetaPDF_Core_Writer_Http('Brand-Guide.pdf', true);
// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/Brand-Guide.pdf', $writer);

// get the outlines helper
$outlines = $document->getCatalog()->getOutlines();

// create an item instance
$item = SetaPDF_Core_Document_OutlinesItem::create($document, '© Setasign');
// make it bold
$item->setBold(true);

// create an Uri action
$action = new SetaPDF_Core_Document_Action_Uri('https://www.setasign.com');
// add the action to the item
$item->setAction($action);

// add it to the root outline
$outlines->appendChild($item);

// show the outline panel
$document->getCatalog()->setPageMode(SetaPDF_Core_Document_PageMode::USE_OUTLINES);

// save and finish the document instance
$document->save()->finish();
