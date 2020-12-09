<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/products/All-Portfolio.pdf'
);

// get the collection instance
$collection = new SetaPDF_Merger_Collection($document);

// get all files
$files = [];
$fileSpecs = $collection->getFiles();
foreach ($fileSpecs as $name => $file) {
    $files[] = [
        'displayValue' => $file->getFileSpecification(),
        'name' => $name
    ];
}

$file = displayFiles($files, false);

// extract the file
$file = $collection->getFile($file['name']);

// resolve the filename
$filename = $file->getFileSpecification();
// resolve the file stream
$embeddedFileStream = $file->getEmbeddedFileStream();

// get the content type
$contentType = $embeddedFileStream->getMimeType();
// or set a default content type
if ($contentType === null) {
    $contentType = 'application/force-download';
}

// pass the file to the client
$stream = $embeddedFileStream->getStream();
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; ' .
    SetaPDF_Core_Writer_Http::encodeFilenameForHttpHeader($filename)
);
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . strlen($stream));
echo $stream;
