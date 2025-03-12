<?php

use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/tektown/invoices/1*.pdf');

$paths = displayFiles($files, true, true);

// create a merger instance
$merger = new Merger();

// iterate through paths...
foreach ($paths as $path) {
    // ... add them to the merger instance
    $merger->addFile($path);
}

// merge
$merger->merge();

// get access to the document instance
$document = $merger->getDocument();
// set a writer instance
$document->setWriter(new HttpWriter('merged.pdf', true));
// and save the result to the writer
$document->save()->finish();
