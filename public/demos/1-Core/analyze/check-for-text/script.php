<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// list some files
$files = glob($assetsDirectory . '/pdfs/*.pdf');
$files = array_merge($files, glob($assetsDirectory . '/pdfs/misc/*.pdf'));

foreach ($files AS $path) {
    $name = basename($path);
    echo '<a href="?f=' . urlencode($path) . '">' . htmlspecialchars($name) . '</a><br />';
}

echo '<br />';
if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    die();
}

// require the text processor class
require_once 'TextProcessor.php';

// load a document instance
$document = SetaPDF_Core_Document::loadByFilename(realpath($_GET['f']));
// get access to the pages object
$pages = $document->getCatalog()->getPages();

// walk through the pages
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $canvas = $pages->getPage($pageNo)->getCanvas();

    // create an text processor instance
    $processor = new TextProcessor($canvas);

    // check for text
    if ($processor->hasText()) {
        echo 'Page ' . $pageNo . ' has text!';
    } else {
        echo 'Page ' . $pageNo . ' has NO text!';
    }

    echo '</br>';
}
