<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$merger = new SetaPDF_Merger();

$filename = $assetsDirectory . '/pdfs/Brand-Guide-Encrypted (owner-pw setasign).pdf';
// use the helper method to register the document instance internally:
$encryptedDocument = $merger->getDocumentByFilename($filename);
if ($encryptedDocument->hasSecHandler()) {
    $secHanlder = $encryptedDocument->getSecHandler();
    $secHanlder->auth('setasign');
}

// add the file (internally the same document instance will be used)
$merger->addFile($filename);

// now create an instance manually
$filename = $assetsDirectory . '/pdfs/Fuchslocher-Example (owner-pw setasign).pdf';
$encryptedDocument = \SetaPDF_Core_Document::loadByFilename($filename);
if ($encryptedDocument->hasSecHandler()) {
    $secHanlder = $encryptedDocument->getSecHandler();
    $secHanlder->auth('setasign');
}

// now use the addDocument() method
$merger->addDocument($encryptedDocument);

// merger
$merger->merge();

$document = $merger->getDocument();
$document->setWriter(new \SetaPDF_Core_Writer_Http('result.pdf', true));
$document->save()->finish();
