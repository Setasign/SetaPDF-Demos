<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Actions.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf'
];

displayFiles($files);

// create a document
$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

// get names
$names = $document->getCatalog()->getNames();
// get the JavaScript name tree
$javaScriptTree = $names->getTree(SetaPDF_Core_Document_Catalog_Names::JAVA_SCRIPT);

if ($javaScriptTree) {
    $allJavaScripts = $javaScriptTree->getAll(false, SetaPDF_Core_Document_Action_JavaScript::class);

    foreach ($allJavaScripts as $name => $jsAction) {
        echo $name . '<br />------------------';
        echo '<pre>';
        echo htmlspecialchars($jsAction->getJavaScript());
        echo '</pre>';
    }
}

if (!isset($allJavaScripts) || count($allJavaScripts) === 0) {
    echo 'No document level JavaScript found!';
}
