<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed-no-LTV.pdf',
    $assetsDirectory . '/pdfs/tektown/Order-Form.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/misc/4-rects-signed-and-locked.pdf'
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

    echo ' used/signed.<br/>';
    $lock = $field->getLock();
    if (is_array($lock)) {
        if ($lock['action'] === SetaPDF_Signer_SignatureField::LOCK_DOCUMENT_ALL) {
            echo '&nbsp;&nbsp;The document is locked by "' . htmlspecialchars($signatureFieldName) . '"<br/>';
        } elseif ($lock['action'] === SetaPDF_Signer_SignatureField::LOCK_DOCUMENT_INCLUDE) {
            echo '&nbsp;&nbsp;Fields are locked by "' . htmlspecialchars($signatureFieldName) . '"<br/>';
            echo '<pre>';
            var_dump(array_map(['SetaPDF_Core_Encoding', 'convertPdfString'], $lock['fields']));
            echo '</pre>';
        } elseif ($lock['action'] === SetaPDF_Signer_SignatureField::LOCK_DOCUMENT_EXCLUDE) {
            echo '&nbsp;&nbsp;Fields are not locked by "' . htmlspecialchars($signatureFieldName) . '"<br/>';
            echo '<pre>';
            var_dump(array_map(['SetaPDF_Core_Encoding', 'convertPdfString'], $lock['fields']));
            echo '</pre>';
        }
    }
    echo '<br/>';
}
