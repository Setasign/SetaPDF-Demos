<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\SecHandler\SecHandler;
use setasign\SetaPDF2\Core\SecHandler\Standard\Aes256;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

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
$writer = new HttpWriter('encrypted.pdf');

// create a document instance
$document = Document::loadByFilename($path, $writer);

// create a security handler instance
$secHandler = Aes256::create(
    $document,
    'owner',
    'user',
    // allow the user to print the document in high quality
    SecHandler::PERM_PRINT | SecHandler::PERM_DIGITAL_PRINT
);

// pass it to the document instance
$document->setSecHandler($secHandler);

// save and finish
$document->save()->finish();
