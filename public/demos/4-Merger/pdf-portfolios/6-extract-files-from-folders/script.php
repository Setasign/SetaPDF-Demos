<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/Logos-Portfolio.pdf'
);

// get the collection instance
$collection = new SetaPDF_Merger_Collection($document);

if (isset($_GET['name'])) {
    $file = $collection->getFile($_GET['name']);
    if ($file instanceof SetaPDF_Core_FileSpecification) {
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
            SetaPDF_Core_Writer_Http::encodeFilenameForHttpHeader($filename)
        );
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($stream));
        echo $stream;
        die();
    }
}

// a simple function which is called recursively to print out all folders and files
function printFolder(SetaPDF_Merger_Collection_Folder $folder, $level = 0) {
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
