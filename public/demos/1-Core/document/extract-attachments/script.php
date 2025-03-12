<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\EmbeddedFileStream;
use setasign\SetaPDF2\Core\FileSpecification;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/products/All-Portfolio.pdf',
    $assetsDirectory . '/pdfs/Logos-Portfolio.pdf',
];

$path = displayFiles($files);

// create a document
$document = Document::loadByFilename($path);

// get names
$names = $document->getCatalog()->getNames();
// get the "embedded files" name tree
$embeddedFiles = $names->getEmbeddedFiles();

// extract the file
if (isset($_GET['name'])) {
    $file = $embeddedFiles->get($_GET['name']);
    if ($file instanceof FileSpecification) {
        // resolve the filename
        $filename = $file->getFileSpecification();
        // resolve the file stream
        $embeddedFileStream = $file->getEmbeddedFileStream();

        // get the content type
        // $contentType = $embeddedFileStream->getMimeType();
        // Sadly this is sometimes faulty or "null", so we force a download here:
        $contentType = 'application/force-download';

        // pass the file to the client
        $stream = $embeddedFileStream->getStream();
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; ' . HttpWriter::encodeFilenameForHttpHeader($filename));
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($stream));
        echo $stream;
        die();
    }
}

$files = $embeddedFiles->getAll();

foreach ($files AS $name => $file) {
    $filename = $file->getFileSpecification();

    $size = null;
    $params = $file->getEmbeddedFileStream()->getParams();
    if (isset($params[EmbeddedFileStream::PARAM_SIZE])) {
        $size = $params[EmbeddedFileStream::PARAM_SIZE];
    }

    echo '<a href="?f=' . urlencode($_GET['f']) . '&name=' . urlencode($name) . '">';
    echo htmlspecialchars($filename) . '</a>';
    if ($size) {
        echo ' (' . $size . ' Bytes)';
    }

    echo '<br />';
}
