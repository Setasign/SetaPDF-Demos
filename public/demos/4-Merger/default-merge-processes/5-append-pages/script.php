<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a http writer object
$writer = new \SetaPDF_Core_Writer_Http('append.pdf', true);

// load the inital document
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Order-Form.pdf', $writer
);

// initiate a merger instance with an initial document
$merger = new \SetaPDF_Merger($document);

// append another complete document
$merger->addFile($assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf');

// now merge the documents
$merger->merge();

// save and finish the initial document
$document->save()->finish();
