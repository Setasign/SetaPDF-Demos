<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a http writer object
$writer = new HttpWriter('append.pdf', true);

// load the inital document
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Order-Form.pdf', $writer
);

// initiate a merger instance with an initial document
$merger = new Merger($document);

// append another complete document
$merger->addFile($assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf');

// now merge the documents
$merger->merge();

// save and finish the initial document
$document->save()->finish();
