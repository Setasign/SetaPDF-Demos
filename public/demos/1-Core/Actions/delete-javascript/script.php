<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Actions.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf'
];

$path = displayFiles($files);

// create a document
$document = SetaPDF_Core_Document::loadByFilename($path);

// get names
$names = $document->getCatalog()->getNames();
// get the JavaScript name tree
$javaScriptTree = $names->getTree(SetaPDF_Core_Document_Catalog_Names::JAVA_SCRIPT);

$out = '';

$shouldSave = false;
if ($javaScriptTree) {
    // walk through all java scripts
    foreach ($javaScriptTree->getAll(true) as $name) {
        $out .= 'Remove: <a href="?f=' . urlencode($_GET['f']) . '&name=' . urlencode($name) . '">' . htmlspecialchars($name) . "</a><br />";
        if (isset($_GET['name']) && $_GET['name'] === $name) {
            $javaScriptTree->remove($name);
            $shouldSave = true;
        }
    }

    if ($shouldSave) {
        $writer = new SetaPDF_Core_Writer_Http();
        $document->setWriter($writer);
        $document->save()->finish();
        die();
    }

} else {
    $out = 'No document level JavaScript found!';
}

echo $out;