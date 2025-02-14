<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new Merger();

$filename = $assetsDirectory . '/pdfs/Brand-Guide-Encrypted (owner-pw setasign).pdf';
// use the helper method to register the document instance internally:
$encryptedDocument = $merger->getDocumentByFilename($filename);
if ($encryptedDocument->hasSecHandler()) {
    $secHandler = $encryptedDocument->getSecHandler();
    $secHandler->auth('setasign');
}

// add the file (internally the same document instance will be used)
$merger->addFile($filename);

// now create an instance manually
$filename = $assetsDirectory . '/pdfs/Fuchslocher-Example (owner-pw setasign).pdf';
$encryptedDocument = Document::loadByFilename($filename);
if ($encryptedDocument->hasSecHandler()) {
    $secHandler = $encryptedDocument->getSecHandler();
    $secHandler->auth('setasign');
}

// now use the addDocument() method
$merger->addDocument($encryptedDocument);

// merge the documents
$merger->merge();

$document = $merger->getDocument();
$document->setWriter(new HttpWriter('result.pdf', true));
$document->save()->finish();
