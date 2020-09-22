<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// list some files
$files = glob($assetDirectory . '/pdfs/*.pdf');
if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    header("Content-Type: text/html; charset=utf-8");
    foreach ($files AS $path) {
        echo '<a href="script.php?f=' . urlencode($path) . '">' . htmlspecialchars(basename($path)) . '</a><br />';
    }
    die();
}

// create a writer
$writer = new SetaPDF_Core_Writer_Http('AddJavaScript.pdf', false);
// create a document
$document = SetaPDF_Core_Document::loadByFilename($_GET['f'], $writer);

// our JavaScript
// We check for an existing JS var in the document.
$js = 'var msg = typeof(SetaPDF) != "undefined" ? SetaPDF : "Hello from SetaPDF!"; app.alert(msg);';
// create an action
$jsAction = new SetaPDF_Core_Document_Action_JavaScript($js);

// get names
$names = $document->getCatalog()->getNames();
// get the JavaScript name tree
$javaScriptTree = $names->getTree(SetaPDF_Core_Document_Catalog_Names::JAVA_SCRIPT, true);

// make sure we've an unique name
$name = 'SetaPDF';
$i = 0;
while ($javaScriptTree->get($name . ' ' . ++$i) !== false);

// Add the JavaScript action to the document
$javaScriptTree->add($name . ' ' . $i, $jsAction->getPdfValue());

// done!
$document->save()->finish();
