<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new SetaPDF_Core_Writer_Http('simple-portfolio.pdf');
$document = new SetaPDF_Core_Document($writer);
$document->getCatalog()->getPages()->create(SetaPDF_Core_PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new SetaPDF_Merger_Collection($document);

// add a file through a local path
$collection->addFile(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    'Laboratory-Report.pdf',
    'Description of Laboratory-Report.pdf'
);

// add a dynamically created text file
$textFile = 'A simple text content';
$collection->addFile(
    new SetaPDF_Core_Reader_String($textFile),
    'text-file.txt',
    'The description of the text file.'
);

// save and finish
$document->save()->finish();
