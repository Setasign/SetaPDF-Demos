<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Reader\StringReader;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Collection;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new HttpWriter('simple-portfolio.pdf');
$document = new Document($writer);
$document->getCatalog()->getPages()->create(PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new Collection($document);

// add a file through a local path
$collection->addFile(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    'Laboratory-Report.pdf',
    'Description of Laboratory-Report.pdf'
);

// add a dynamically created text file
$textFile = 'A simple text content';
$collection->addFile(
    new StringReader($textFile),
    'text-file.txt',
    'The description of the text file.'
);

// save and finish
$document->save()->finish();
