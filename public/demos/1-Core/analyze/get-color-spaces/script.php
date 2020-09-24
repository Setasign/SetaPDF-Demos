<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// prepare some files
$files = glob($assetsDirectory . '/pdfs/misc/*.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';
$files[] = $assetsDirectory . '/pdfs/Brand-Guide - with-comments.pdf';

// if we have a file, let's process it
if (isset($_GET['f']) && in_array($_GET['f'], $files, true)) {

    // require helper classes
    require_once 'ColorProcessor.php';
    require_once 'ColorInspector.php';

    $document = SetaPDF_Core_Document::loadByFilename($_GET['f']);
    $inspector = new ColorInspector($document);
    $colors = $inspector->getColors();

    echo '<pre>';
    if (count($colors) === 0) {
        echo 'No color definitions found.';
        exit();
    }

    $allColorSpaces = [];
    foreach ($colors AS $color) {
        $allColorSpaces[$color['colorSpace']] = $color['colorSpace'];
    }

    echo 'Color space(s) found: ' . implode(', ', $allColorSpaces) . '<br/><br/>';

    foreach ($colors AS $color) {
        $className = get_class($color['data']);
        echo $color['colorSpace'] . ': ' . $className . '<br/>';

        switch ($className) {
            case SetaPDF_Core_ColorSpace_Separation::class:
                /** @var SetaPDF_Core_ColorSpace_Separation $data */
                $data = $color['data'];
                echo '    Name: ' . $data->getName() . '<br/>';
                echo '    Alt: ' . $data->getAlternateColorSpace()->getFamily() . '<br/>';
                break;

            case SetaPDF_Core_ColorSpace_IccBased::class:
                /** @var SetaPDF_Core_ColorSpace_IccBased $data */
                $data = $color['data'];
                $parser = $data->getIccProfileStream()->getParser();
                echo '    Description: ' . $parser->getDescription() . '<br/>';
                echo '    Number of components: ' . $parser->getNumberOfComponents() . '<br/>';
                break;
        }

        echo '    Location: ' . $color['location'] . '<br/>';
        echo '    Info: ' . $color['info'] . '<br/>';
        echo '<br/>';
    }

    echo '</pre>';

} else {

    // list the files
    foreach ($files AS $path) {
        $name = basename($path);
        echo '<a href="?f=' . urlencode($path) . '" target="pdfFrame">' . htmlspecialchars($name) . '</a><br />';
    }

    echo '<br />';
    echo '<iframe width="100%" height="300" name="pdfFrame" src="about:blank"/>';

}