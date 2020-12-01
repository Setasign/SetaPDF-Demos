<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a merger instance
$merger = new SetaPDF_Merger();

$file = $assetsDirectory . '/pdfs/misc/large/1000-red.pdf';

// page 1 to 50
$merger->addFile($file, '1-50');
// also possible with the addDocument() method:
//$documentToMerge = $merger->getDocumentByFilename($file);
//$merger->addDocument($documentToMerge, '1-50');

// page 51, 53, 55
$merger->addFile($file, [51, 53, 55]);

// page 100 - 200 through a callback
$merger->addFile($file, function($pageNo) {
    return $pageNo >= 100 && $pageNo <= 200;
});

// page 950 - last page
$merger->addFile($file, '950-');

// add the last page again
$merger->addFile($file, SetaPDF_Merger::PAGES_LAST);

$merger->merge();

// get access to the document instance
$document = $merger->getDocument();
// set a writer instance
$document->setWriter(new SetaPDF_Core_Writer_Http('merged.pdf', true));
// and save the result to the writer
$document->save()->finish();
