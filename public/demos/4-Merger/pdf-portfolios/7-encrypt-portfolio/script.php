<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new \SetaPDF_Core_Writer_Http('encrypted.pdf');
$document = new \SetaPDF_Core_Document($writer);
$document->getCatalog()->getPages()->create(\SetaPDF_Core_PageFormats::A4);
// we leave it empty for demonstration purpose...

$secHandler = SetaPDF_Core_SecHandler_Standard_Aes256::factory(
    $document,
    'owner-password',
    'user-password'
);

$document->setSecHandler($secHandler);

// create a collection instance
$collection = new \SetaPDF_Merger_Collection($document);

// add a file through a local path
$name = $collection->addFile(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    'Laboratory-Report-signed.pdf'
);

// instruct the viewer application to display the document initially
$collection->setInitialDocument($name);

// save and finish
$document->save()->finish();
