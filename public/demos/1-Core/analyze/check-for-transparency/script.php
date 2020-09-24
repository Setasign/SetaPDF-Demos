<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// prepare some files
$files = glob($assetsDirectory . '/pdfs/misc/*.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';

// if we have a file, let's process it
if (isset($_GET['f']) && in_array($_GET['f'], $files, true)) {

    // require the text processor class
    require_once 'TransparencyInspector.php';

    $document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

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

} else {

    // list the files
    foreach ($files AS $path) {
        $name = basename($path);
        echo '<a href="?f=' . urlencode($path) . '" target="pdfFrame">' . htmlspecialchars($name) . '</a><br />';
    }

    echo '<br />';
    echo '<iframe width="100%" height="300" name="pdfFrame" src="about:blank"/>';

}