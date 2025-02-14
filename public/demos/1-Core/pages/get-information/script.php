<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageBoundaries;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

$path = displayFiles($files);

// create a document instance
$document = Document::loadByFilename($path);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// iterate through the document page by page and get some properties
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    echo 'Page No.: ' . $pageNo . '<br >';

    // get the page object
    $page = $pages->getPage($pageNo);

    // print all page boundaries
    foreach (PageBoundaries::$all AS $boxName) {
        $box = $page->getBoundary($boxName);
        echo $boxName;
        vprintf(' = [llx: %.3F, lly: %.3F, urx: %.3F, ury: %.3F]<br />', $box->toPhp());
    }

    // Width and height:
    list($width, $height) = $page->getWidthAndHeight();
    echo 'Width: ' . $width . ' pt<br />';
    echo 'Height: ' . $height . ' pt<br />';

    // print the page rotation value
    echo 'Rotation: ' . $page->getRotation() . '<br />';

    echo '<br /><br />';
}