<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed-no-LTV.pdf',
    $assetsDirectory . '/pdfs/tektown/Order-Form.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf'
];

$path = displayFiles($files);

$document = \SetaPDF_Core_Document::loadByFilename($path);

$signatureFieldNames = \SetaPDF_Signer_ValidationRelatedInfo_Collector::getSignatureFieldNames($document);
foreach ($signatureFieldNames as $signatureFieldName) {
    echo htmlspecialchars(sprintf('Signature Field "%s" is ', $signatureFieldName));

    $field = \SetaPDF_Signer_SignatureField::get($document, $signatureFieldName);
    if ($field->getValue() === null) {
        echo '<b>NOT</b>';
    }

    echo ' used/signed.<br/><br/>';
}
