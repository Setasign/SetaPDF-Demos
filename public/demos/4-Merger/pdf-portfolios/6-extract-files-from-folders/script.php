<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\FileSpecification;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Collection;
use setasign\SetaPDF2\Merger\Collection\Folder;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Logos-Portfolio.pdf'
);

// get the collection instance
$collection = new Collection($document);

if (isset($_GET['name'])) {
    $file = $collection->getFile($_GET['name']);
    if ($file instanceof FileSpecification) {
        // resolve the filename
        $filename = $file->getFileSpecification();
        // resolve the file stream
        $embeddedFileStream = $file->getEmbeddedFileStream();

        // sadly the embedded mime-type can be faulty, so...
        // ...we force a content type
        $contentType = 'application/force-download';

        // pass the file to the client
        $stream = $embeddedFileStream->getStream();
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; ' .
            HttpWriter::encodeFilenameForHttpHeader($filename)
        );
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($stream));
        echo $stream;
        die();
    }
}

// a simple function which is called recursively to print out all folders and files
function printFolder(Folder $folder, $level = 0) {
    $files = $folder->getFiles();

    echo str_repeat('&nbsp', $level++ * 4);
    echo htmlspecialchars($folder->getName()) . '/<br />';
    foreach ($files AS $name => $file) {
        $filename = $file->getFileSpecification();
        echo str_repeat('&nbsp', $level * 4);
        echo '<a href="?name=' . urlencode($name) . '">' . htmlspecialchars($filename) . '</a><br />';
    }

    // get sub folders and print them out, too
    foreach ($folder->getSubfolders() AS $subFolder) {
        printFolder($subFolder, $level);
    }
}

printFolder($collection->getRootFolder());
