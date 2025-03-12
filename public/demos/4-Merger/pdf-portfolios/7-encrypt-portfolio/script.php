<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Collection;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new HttpWriter('encrypted.pdf');
$document = new Document($writer);
$document->getCatalog()->getPages()->create(PageFormats::A4);
// we leave it empty for demonstration purpose...

$secHandler = \setasign\SetaPDF2\Core\SecHandler\Standard\Aes256::create(
    $document,
    'owner-password',
    'user-password'
);

$document->setSecHandler($secHandler);

// create a collection instance
$collection = new Collection($document);

// add a file through a local path
$name = $collection->addFile(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    'Laboratory-Report-signed.pdf'
);

// instruct the viewer application to display the document initially
$collection->setInitialDocument($name);

// save and finish
$document->save()->finish();
