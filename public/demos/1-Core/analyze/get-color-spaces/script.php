<?php

// load and register the autoload function
use com\setasign\SetaPDF\Demos\Inspector\ColorInspector;

require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/pdfs/misc/*.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';
$files[] = $assetsDirectory . '/pdfs/Brand-Guide - with-comments.pdf';

$path = displayFiles($files);

// require helper classes
require_once $classesDirectory . '/ContentStreamProcessor/ColorProcessor.php';
require_once $classesDirectory . '/Inspector/ColorInspector.php';

$document = SetaPDF_Core_Document::loadByFilename($path);
$inspector = new ColorInspector($document);
$colors = $inspector->getColors();

if (count($colors) === 0) {
    echo 'No color definitions found.';
    exit();
}

$allColorSpaces = [];
foreach ($colors AS $color) {
    $allColorSpaces[$color['colorSpace']] = $color['colorSpace'];
}

echo '<pre>';
echo 'Color space(s) found: ' . implode(', ', $allColorSpaces) . "\n\n";

foreach ($colors AS $color) {
    $className = get_class($color['data']);
    echo $color['colorSpace'] . ': ' . $className . "\n";

    switch ($className) {
        case SetaPDF_Core_ColorSpace_Separation::class:
            /** @var SetaPDF_Core_ColorSpace_Separation $data */
            $data = $color['data'];
            echo '    Name: ' . $data->getName() . "\n";
            echo '    Alt: ' . $data->getAlternateColorSpace()->getFamily() . "\n";
            break;

        case SetaPDF_Core_ColorSpace_IccBased::class:
            /** @var SetaPDF_Core_ColorSpace_IccBased $data */
            $data = $color['data'];
            $parser = $data->getIccProfileStream()->getParser();
            echo '    Description: ' . $parser->getDescription() . "\n";
            echo '    Number of components: ' . $parser->getNumberOfComponents() . "\n";
            break;
    }

    echo '    Location: ' . $color['location'] . "\n";
    echo '    Info: ' . $color['info'] . "\n";
    echo "\n";
}

echo '</pre>';
