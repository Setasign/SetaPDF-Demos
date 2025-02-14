<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\EmbeddedFileStream;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Reader\StringReader;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Collection;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new HttpWriter('dynamic-portfolio.pdf');
$document = new Document($writer);
$document->getCatalog()->getPages()->create(PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new Collection($document);

// add a dynamically created text file
$textFile = 'A simple text content';
$collection->addFile(
    new StringReader($textFile),
    'text-file.txt',
    'The description of the text file.',
    [
        // an optional check sum
        EmbeddedFileStream::PARAM_CHECK_SUM => md5($textFile, true),
        // modification and creation date are default columns and set automatically
        // to the current date time. If you want to define them manually:
        EmbeddedFileStream::PARAM_MODIFICATION_DATE => new DateTime('yesterday'),
        EmbeddedFileStream::PARAM_CREATION_DATE => new DateTime('-1 week')
    ],
    'text/plain'
);

// add another dynamically created text file
$textFile = 'Another simple text content';
$name = $collection->addFile(
    new StringReader($textFile),
    'another-text-file.txt',
    'The description of the other text file.',
    [],
    'text/plain'
);
// get the file specification by its name
$fileSpecification = $collection->getFile($name);

// get the embedded file stream and add additional parameters
$fileSpecification->getEmbeddedFileStream()->setParams([
    EmbeddedFileStream::PARAM_CHECK_SUM => md5($textFile, true),
    EmbeddedFileStream::PARAM_MODIFICATION_DATE => new DateTime('yesterday'),
    EmbeddedFileStream::PARAM_CREATION_DATE => new DateTime('last Wednesday')
], false);

// save and finish
$document->save()->finish();
