<?php

require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = [
    $assetDirectory . '/pdfs/Brand-Guide.pdf',
    $assetDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetDirectory . '/pdfs/etown/Laboratory-Report.pdf',
];

if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    foreach ($files as $path) {
        $name = basename($path);
        echo '<a href="?f=' . urlencode($path) . '">';
        echo htmlspecialchars($name, ENT_QUOTES | ENT_HTML5);
        echo '</a><br />';
    }

    echo '<br />';
    die();
}

//require_once('library/SetaPDF/Autoload.php');
// or if you use composer require_once('vendor/autoload.php');

$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

$pages = $document->getCatalog()->getPages();
$pageCount = $pages->count();
// or
// $pageCount = count($pages);

echo 'The document "' . basename($_GET['f']) . '" has ' .
    ($pageCount == 1 ? '1 page' : $pageCount . ' pages');
