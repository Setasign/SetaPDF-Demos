<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf'
];

$path = displayFiles($files);

// create a reader
$reader = new SetaPDF_Core_Reader_File($path);
// create a writer
$writer = new SetaPDF_Core_Writer_Http('add-pages.pdf', true);
// create a document
$document = SetaPDF_Core_Document::load($reader, $writer);

// Get the pages helper
$pages = $document->getCatalog()->getPages();

// create a new blank last page and automatically append it
$newLastPage = $pages->create(SetaPDF_Core_PageFormats::A4);

/* create a new blank page in landscape format and pass
 * false to the $append parameter so we can prepend it afterwards.
 */
$newFirstPage = $pages->create(SetaPDF_Core_PageFormats::A4, SetaPDF_Core_PageFormats::ORIENTATION_LANDSCAPE, false);
$pages->prepend($newFirstPage);

// remove the OpenAction
$document->getCatalog()->getDictionary()->offsetUnset('OpenAction');
// or (Revision > 1583)
//$document->getCatalog->setOpenAction(null);

// save the complete document
$document->save(true)->finish();