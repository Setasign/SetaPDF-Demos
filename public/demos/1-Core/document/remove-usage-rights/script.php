<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example-ReaderEnabled.pdf',
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
];

$path = displayFiles($files, false);

// create a document
$document = SetaPDF_Core_Document::loadByFilename($path);

$permissions = $document->getCatalog()->getPermissions();

// check for usage right
if ($permissions->hasUsageRights()) {
    // remove them
    $permissions->removeUsageRights();

    // save the document
    $document->setWriter(new SetaPDF_Core_Writer_Http('no-usage-rights.pdf'));
    $document->save()->finish();
} else {
    echo 'No usage rights found.';
}