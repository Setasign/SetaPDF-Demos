<?php

use setasign\SetaPDF2\Demos\Inspector\TransparencyInspector;
use setasign\SetaPDF2\Core\Document;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/pdfs/misc/*.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';

$path = displayFiles($files);

// require the text processor class
require_once $classesDirectory . '/Inspector/TransparencyInspector.php';

$document = Document::loadByFilename($path);

$inspector = new TransparencyInspector($document);
$transparencyElements = $inspector->process();

foreach ($transparencyElements as $element) {
    echo 'Type: ' . $element['type'] . '<br />';
    echo 'Info: ' . $element['info'] . '<br />';
    echo 'Location: ' . $element['location'] . '<br />';
    echo 'Data (class name): ' . get_class($element['data']) . '<br />';
    echo "<br />";
}

if (count($transparencyElements) === 0) {
    echo 'No transparency found.';
}
