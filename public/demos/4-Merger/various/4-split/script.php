<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$pdfPath = $assetsDirectory . '/pdfs/Brand-Guide.pdf';

// let's prepare a ZipArchive instance
$zip = new ZipArchive();
$zipName = tempnam(sys_get_temp_dir(), 'zip');
$zip->open($zipName, ZipArchive::CREATE);

// load the "in"-document
$inDocument = SetaPDF_Core_Document::loadByFilename($pdfPath);
// to prevent multiple object resolving set this to true
$inDocument->setCacheReferencedObjects(true);
// keep read objects for reusage for other pages
$inDocument->setCleanUpObjects(false);
// we want to work with the pages
$pages = $inDocument->getCatalog()->getPages();
// we will touch them all, so pre-read them all (will speed things up)
$pages->ensureAllPageObjects();

// now extract page by page
for ($pageNumber = 1, $pageCount = $pages->count(); $pageNumber <= $pageCount; $pageNumber++) {

    // we create a new merger instance
    $merger = new SetaPDF_Merger();
    // add the individual page of the "in"-document to the merger
    $merger->addDocument($inDocument, $pageNumber);
    // ...and merge
    $merger->merge();

    // create a writer which we can pass to the ZipArchive instance
    $writer = new SetaPDF_Core_Writer_String();

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
    SetaPDF_Core_Writer_Http::encodeFilenameForHttpHeader(basename($pdfPath, '.pdf') . '.zip')
);
readfile($zipName);

unlink($zipName);
