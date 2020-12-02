<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$shareObjects = displaySelect('Share objects:', [1 => 'yes', 0 => 'no']);

// initiate a merger instance with an initial document
$merger = new SetaPDF_Merger();

$path = $assetsDirectory . '/pdfs/camtown/Letterhead.pdf';
for ($i = 100; $i > 0; $i--) {
    if ($shareObjects) {
        $merger->addFile($path, 1);
    } else {
        // load the inital document
        $document = SetaPDF_Core_Document::loadByFilename($path);
        $merger->addDocument($document, 1);
    }
}

// now merge the documents
$merger->merge();

$document = $merger->getDocument();
$document->setWriter(new SetaPDF_Core_Writer_Http('repeated.pdf', true));

// save and finish the initial document
$document->save()->finish();
