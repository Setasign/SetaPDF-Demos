<?php

use com\setasign\SetaPDF\Demos\Inspector\FontInspector;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = glob($assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';
$files[] = $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf';
$files[] = $assetsDirectory . '/pdfs/misc/Handwritten-Signature.pdf';

$path = displayFiles($files);

require_once $classesDirectory . '/Inspector/FontInspector.php';

$fontInspector = new FontInspector($path);
$fontObjects = $fontInspector->resolveFonts();

foreach ($fontObjects AS $fontObject) {
    try {
        $font = SetaPDF_Core_Font::get($fontObject);
    } catch (Exception $e) {
        echo $e->getMessage();
        continue;
    }

    echo 'Font name: <b>' . $font->getFontName() . '</b> (' . $font->getType() . ')<br />';
    echo 'Embedded: ' . ($fontInspector->isFontEmbedded($font) ? 'yes' : 'no');
    echo '<br /><br />';
}

if (count($fontObjects) === 0) {
    echo 'No fonts found.';
}
