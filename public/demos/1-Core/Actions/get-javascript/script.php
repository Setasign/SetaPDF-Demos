<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// list some files
$files = glob($assetsDirectory . '/pdfs/*.pdf');
if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    header("Content-Type: text/html; charset=utf-8");
    foreach ($files AS $path) {
        echo '<a href="?f=' . urlencode($path) . '" target="pdfFrame">' . htmlspecialchars(basename($path)) . '</a><br />';
    }

    echo '<br />';
    echo '<iframe width="100%" height="360" id="pdfFrame" name="pdfFrame" src="about:blank"/>';
    die();
}

// create a document
$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

// get names
$names = $document->getCatalog()->getNames();
// get the JavaScript name tree
$javaScriptTree = $names->getTree(SetaPDF_Core_Document_Catalog_Names::JAVA_SCRIPT);

if ($javaScriptTree) {
    $allJavaScripts = $javaScriptTree->getAll(false, SetaPDF_Core_Document_Action_JavaScript::class);

    foreach ($allJavaScripts AS $name => $jsAction) {
        echo $name . '<br />------------------';
        echo '<pre>';
        echo htmlspecialchars($jsAction->getJavaScript());
        echo '</pre>';
    }
}

if (!isset($allJavaScripts) || count($allJavaScripts) === 0) {
    echo 'No document level JavaScript found!';
}