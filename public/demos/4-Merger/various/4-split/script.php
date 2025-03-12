<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\Writer\StringWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$pdfPath = $assetsDirectory . '/pdfs/Brand-Guide.pdf';

// let's prepare a ZipArchive instance
$zip = new \ZipArchive();
$zipName = tempnam(sys_get_temp_dir(), 'zip');
unlink($zipName);
$zip->open($zipName, \ZipArchive::CREATE);

// load the "in"-document
$inDocument = Document::loadByFilename($pdfPath);
// to prevent multiple object resolving set this to true
$inDocument->setCacheReferencedObjects(true);
// keep read objects for re-usage for other pages
$inDocument->setCleanUpObjects(false);
// we want to work with the pages
$pages = $inDocument->getCatalog()->getPages();
// we will touch them all, so pre-read them all (will speed things up)
$pages->ensureAllPageObjects();

// now extract page by page
for ($pageNumber = 1, $pageCount = $pages->count(); $pageNumber <= $pageCount; $pageNumber++) {

    // we create a new merger instance
    $merger = new Merger();
    // add the individual page of the "in"-document to the merger
    $merger->addDocument($inDocument, $pageNumber);
    // ...and merge
    $merger->merge();

    // create a writer which we can pass to the ZipArchive instance
    $writer = new StringWriter();

    // get the resulting document instance
    $resDocument = $merger->getDocument();
    /* define that written objects should not be cleaned-up (we need this,
     * because we are going to re-use them for coming pages of the "in"-document
     */
    $resDocument->setCleanUpObjects(false);
    // set the writer
    $resDocument->setWriter($writer);
    // save and finish the extracted page/document
    $resDocument->save()->finish();
    // free some memory
    $resDocument->cleanUp();

    // let's create a sortable filename
    $pdfName = sprintf('%0' . strlen($pageCount). 's', $pageNumber) . '.pdf';
    // add the file to the zip archive
    $zip->addFromString($pdfName, $writer);
}

// close the zip file and send the zip-file to the client
$zip->close();

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($zipName));
header('Content-Disposition: attachment; ' .
    HttpWriter::encodeFilenameForHttpHeader(basename($pdfPath, '.pdf') . '.zip')
);
readfile($zipName);

unlink($zipName);
