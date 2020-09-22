<?php

require_once __DIR__ . '/../../../../../bootstrap.php';

$files = array_merge([
    $assetDirectory . '/pdfs/Brand-Guide.pdf',
    $assetDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetDirectory . '/pdfs/etown/Laboratory-Report.pdf',
], $sessionFiles);

if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    foreach ($files as $path) {
        $name = basename($path);
        echo '<a href="?f=' . urlencode($path) . '" target="pdfFrame">';
        echo htmlspecialchars($name, ENT_QUOTES | ENT_HTML5);
        echo '</a><br />';
    }

    echo '<br />';
    echo '<iframe width="100%" height="500" id="pdfFrame" name="pdfFrame" src="about:blank"/>';
    die();
}

//require_once('library/SetaPDF/Autoload.php');
// or if you use composer require_once('vendor/autoload.php');

// create a file writer
$writer = new SetaPDF_Core_Writer_Http('rotated.pdf', true);
// load document by filename
$document = SetaPDF_Core_Document::loadByFilename($_GET['f'], $writer);

// get pages object
$pages = $document->getCatalog()->getPages();
// get page count
$pageCount = $pages->count();

for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
    // get page object for this page
    $page = $pages->getPage($pageNumber);

    // rotate by...
    $page->rotateBy(90);
}

// save and finish the document
$document->save()->finish();
