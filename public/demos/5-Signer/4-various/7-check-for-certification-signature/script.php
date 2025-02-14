<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-certified-form-fill-and-sign.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-certified-no-changes-allowed.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-certified-annotating-form-fill-and-sign.pdf'
];

$file = displayFiles($files, true, false, true);
if (is_array($file)) {
    extract($file);
} else {
    $filename = basename($file);
}

try {
    $document = Document::loadByFilename($file);
    $certificationLevel = Signer::getCertificationLevelByDocument($document);
    if ($certificationLevel === null) {
        echo "Document is not certified.";
        die();
    }

    echo '<span style="color:#22caff;">Document has a certification signature!</span><br />';

    if ($certificationLevel === Signer::CERTIFICATION_LEVEL_NO_CHANGES_ALLOWED) {
        echo '<span style="color:red">No changes allowed.</span>';
    } elseif ($certificationLevel === Signer::CERTIFICATION_LEVEL_FORM_FILLING) {
        echo '<span style="color:green">Form filling and signing is allowed.</span>';
    } elseif ($certificationLevel === Signer::CERTIFICATION_LEVEL_FORM_FILLING_AND_ANNOTATIONS) {
        echo '<span style="color:green">Annotating, form filling and signing is allowed.</span>';
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage();
}
