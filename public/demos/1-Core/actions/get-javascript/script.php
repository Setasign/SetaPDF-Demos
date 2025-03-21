<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\JavaScriptAction;
use setasign\SetaPDF2\Core\Document\Catalog\Names;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Actions.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf'
];

$path = displayFiles($files);

// create a document
$document = Document::loadByFilename($path);

// get names
$names = $document->getCatalog()->getNames();
// get the JavaScript name tree
$javaScriptTree = $names->getTree(Names::JAVA_SCRIPT);

if ($javaScriptTree) {
    $allJavaScripts = $javaScriptTree->getAll(false, JavaScriptAction::class);

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
