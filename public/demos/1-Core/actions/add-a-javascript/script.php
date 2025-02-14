<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\JavaScriptAction;
use setasign\SetaPDF2\Core\Document\Catalog\Names;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Actions.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf'
];

$path = displayFiles($files, false);

// create a writer instance
$writer = new HttpWriter('AddJavaScript.pdf', false);
// create a document instance
$document = Document::loadByFilename($path, $writer);

// this is our JavaScript we want to inject - we check for an
// existing JavaScript variable in the document to show some logic.
$js = 'var msg = typeof(SetaPDF) != "undefined" ? SetaPDF : "Hello from SetaPDF!"; app.alert(msg);';
// create an action
$jsAction = new JavaScriptAction($js);

// get names
$names = $document->getCatalog()->getNames();
// get the JavaScript name tree
$javaScriptTree = $names->getTree(Names::JAVA_SCRIPT, true);

// make sure we've a unique name
$name = 'SetaPDF';
$i = 0;
while ($javaScriptTree->get($name . ' ' . ++$i) !== false);

// add the JavaScript action to the document
$javaScriptTree->add($name . ' ' . $i, $jsAction->getPdfValue());

// done!
$document->save()->finish();
