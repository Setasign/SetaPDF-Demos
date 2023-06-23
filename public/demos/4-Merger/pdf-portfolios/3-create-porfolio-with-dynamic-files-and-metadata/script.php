<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new \SetaPDF_Core_Writer_Http('dynamic-portfolio.pdf');
$document = new \SetaPDF_Core_Document($writer);
$document->getCatalog()->getPages()->create(\SetaPDF_Core_PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new SetaPDF_Merger_Collection($document);

// add a dynamically created text file
$textFile = 'A simple text content';
$collection->addFile(
    new \SetaPDF_Core_Reader_String($textFile),
    'text-file.txt',
    'The description of the text file.',
    [
        // an optional check sum
        \SetaPDF_Core_EmbeddedFileStream::PARAM_CHECK_SUM => md5($textFile, true),
        // modification and creation date are default columns and set automatically
        // to the current date time. If you want to define them manually:
        \SetaPDF_Core_EmbeddedFileStream::PARAM_MODIFICATION_DATE => new DateTime('yesterday'),
        \SetaPDF_Core_EmbeddedFileStream::PARAM_CREATION_DATE => new DateTime('-1 week')
    ],
    'text/plain'
);

// add another dynamically created text file
$textFile = 'Another simple text content';
$name = $collection->addFile(
    new \SetaPDF_Core_Reader_String($textFile),
    'another-text-file.txt',
    'The description of the other text file.',
    [],
    'text/plain'
);
// get the file specification by its name
$fileSpecification = $collection->getFile($name);

// get the embedded file stream and add additional parameters
$fileSpecification->getEmbeddedFileStream()->setParams([
    \SetaPDF_Core_EmbeddedFileStream::PARAM_CHECK_SUM => md5($textFile, true),
    \SetaPDF_Core_EmbeddedFileStream::PARAM_MODIFICATION_DATE => new DateTime('yesterday'),
    \SetaPDF_Core_EmbeddedFileStream::PARAM_CREATION_DATE => new DateTime('last Wednesday')
], false);

// save and finish
$document->save()->finish();
