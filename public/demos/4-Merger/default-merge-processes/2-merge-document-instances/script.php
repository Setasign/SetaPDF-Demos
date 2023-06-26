<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = glob($assetsDirectory . '/pdfs/tektown/invoices/1*.pdf');

$paths = displayFiles($files, true, true);

// create a merger instance
$merger = new \SetaPDF_Merger();

// iterate through paths...
foreach ($paths as $path) {
    // ...for demonstration we initiate the document instances from a string variable
    $pdfString = file_get_contents($path);
    $document = \SetaPDF_Core_Document::loadByString($pdfString);
    $merger->addDocument($document);
}

// merge
$merger->merge();

// get access to the document instance
$document = $merger->getDocument();
// set a writer instance
$document->setWriter(new \SetaPDF_Core_Writer_Http('merged.pdf', true));
// and save the result to the writer
$document->save()->finish();
