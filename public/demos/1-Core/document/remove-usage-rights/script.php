<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example-ReaderEnabled.pdf',
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
];

$path = displayFiles($files, false);

// create a document
$document = Document::loadByFilename($path);

$permissions = $document->getCatalog()->getPermissions();

// check for usage right
if ($permissions->hasUsageRights()) {
    // remove them
    $permissions->removeUsageRights();

    // save the document
    $document->setWriter(new HttpWriter('no-usage-rights.pdf'));
    $document->save()->finish();
} else {
    echo 'No usage rights found.';
}