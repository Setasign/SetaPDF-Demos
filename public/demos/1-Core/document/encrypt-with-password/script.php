<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf'
];

$path = displayFiles($files, false);

// create a writer instance
$writer = new SetaPDF_Core_Writer_Http('encrypted.pdf');

// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

// create a security handler instance
$secHandler = SetaPDF_Core_SecHandler_Standard_Aes256::factory(
    $document,
    'owner',
    'user',
    // allow the user to print the document in high quality
    SetaPDF_Core_SecHandler::PERM_PRINT | SetaPDF_Core_SecHandler::PERM_DIGITAL_PRINT
);

// pass it to the document instance
$document->setSecHandler($secHandler);

// save and finish
$document->save()->finish();
