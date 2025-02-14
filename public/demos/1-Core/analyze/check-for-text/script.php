<?php

use com\setasign\SetaPDF\Demos\ContentStreamProcessor\TextProcessor;
use setasign\SetaPDF2\Core\Document;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
];
$files = array_merge($files, glob($assetsDirectory . '/pdfs/misc/*.pdf'));

$path = displayFiles($files);

// require the text processor class
require_once $classesDirectory . '/ContentStreamProcessor/TextProcessor.php';

// load a document instance
$document = Document::loadByFilename($path);
// get access to the pages object
$pages = $document->getCatalog()->getPages();

// walk through the pages
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $canvas = $pages->getPage($pageNo)->getCanvas();

    // create a text processor instance
    $processor = new TextProcessor($canvas);

    // check for text
    if ($processor->hasText()) {
        echo 'Page ' . $pageNo . ' has text!';
    } else {
        echo 'Page ' . $pageNo . ' has NO text!';
    }

    echo '</br>';
}
