<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$shareObjects = displaySelect('Share objects:', [1 => 'yes', 0 => 'no']);

// initiate a merger instance with an initial document
$merger = new Merger();

$path = $assetsDirectory . '/pdfs/camtown/Letterhead.pdf';
for ($i = 100; $i > 0; $i--) {
    if ($shareObjects) {
        $merger->addFile($path, 1);
    } else {
        // load the initial document
        $document = Document::loadByFilename($path);
        $merger->addDocument($document, 1);
    }
}

// now merge the documents
$merger->merge();

$document = $merger->getDocument();
$document->setWriter(new HttpWriter('repeated.pdf', true));

// save and finish the initial document
$document->save()->finish();
