<?php
// display some links to the named destinations to have some interactivity in this demo
if (!isset($_GET['e'])) {
    echo '<html><head><link rel="stylesheet" type="text/css" href="/layout/demo.css"/></head><body><div id="demoInput">';

    foreach (['product-1', 'product-2', 'product-3'] as $destination) {
        echo '<a href="?e=1#' . $destination . '" target="pdfFrame">Named Destination "' . $destination . '"</a><br/>';
    }

    echo '</div><iframe width="100%" name="pdfFrame" src="about:blank"/></body></html>';
    die();
}

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a merger instance
$merger = new SetaPDF_Merger();

// add the first file and add a named destination named "product-1"
$merger->addFile(
    $assetsDirectory . '/pdfs/tektown/products/Boombastic-Box.pdf',
    SetaPDF_Merger::PAGES_ALL,
    'product-1'
);

// add page 2 of another file and add a named destination named "product-2"
$merger->addFile(
    $assetsDirectory . '/pdfs/tektown/products/All.pdf',
    2,
    'product-2'
);

// add page 3 of another file and add a named destination named "product-3"
$merger->addFile(
    // it is also possible to pass an array with named arguments
    [
        'filename' => $assetsDirectory . '/pdfs/tektown/products/All.pdf',
        'pages' => 3,
        'name' => 'product-3'
    ]
);

$merger->merge();

// get access to the document instance
$document = $merger->getDocument();
// set a writer instance
$document->setWriter(new SetaPDF_Core_Writer_Http('merged-with-named-destinations.pdf', true));
// and save the result to the writer
$document->save()->finish();
