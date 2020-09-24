<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// prepare some files
$files = glob($assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf');
$files[] = $assetsDirectory . '/pdfs/Brand-Guide.pdf';
$files[] = $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf';
$files[] = $assetsDirectory . '/pdfs/misc/Handwritten-Signature.pdf';

if (isset($_GET['f']) && in_array($_GET['f'], $files, true)) {

    require_once 'FontInspector.php';

    $fontInspector = new FontInspector($_GET['f']);
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

} else {

    // list the files
    foreach ($files AS $path) {
        $name = basename($path);
        echo '<a href="?f=' . urlencode($path) . '" target="pdfFrame">' . htmlspecialchars($name) . '</a><br />';
    }

    echo '<br />';
    echo '<iframe width="100%" height="300" name="pdfFrame" src="about:blank"/>';

}