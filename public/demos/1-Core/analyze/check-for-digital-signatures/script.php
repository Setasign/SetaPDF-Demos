<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed-no-LTV.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/tektown/Order-Form.pdf',
    $assetsDirectory . '/pdfs/tektown/eBook-Invoice.pdf',
];

$path = displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($path);
$terminalFields = $document->getCatalog()->getAcroForm()->getTerminalFieldsObjects();

$signatureFieldFound = false;

foreach ($terminalFields as $fieldData) {
    $fieldData = $fieldData->ensure();

    $ft = SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute($fieldData, 'FT');
    if (!$ft || $ft->getValue() !== 'Sig') {
        continue;
    }

    $fieldName = SetaPDF_Core_Document_Catalog_AcroForm::resolveFieldName($fieldData);
    echo sprintf('Signature Field "%s" found! ', $fieldName);
    $signatureFieldFound = true;

    $v = SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute($fieldData, 'V');
    if (!$v || !$v->ensure() instanceof SetaPDF_Core_Type_Dictionary) {
        echo ' But not digital signed.<br /><br />';
        continue;
    }

    echo ' Including a digital signature.<br />';

    // This is the signature value
    $signatureData = $v->ensure()->getValue('Contents')->ensure()->getValue();
    $signatureData = rtrim($signatureData, "\0");

    echo '<a href="https://lapo.it/asn1js/#' . SetaPDF_Core_Type_HexString::str2hex($signatureData) . '" ' .
        'target="_blank">asn1js</a> | ';
    echo '<a href="data:application/pkcs7-mime;base64,' . base64_encode($signatureData) . '" ' .
        'download="signature.pkcs7">download</a><br />';

    echo '<br />';

    $value = $v->ensure();
    $signatureProperties = [];
    foreach (['Name', 'Location', 'ContactInfo', 'Reason', 'M'] as $property) {
        if (!$value->offsetExists($property)) {
            continue;
        }

        $propertyValue = $value->getValue($property)->ensure()->getValue();
        if ($property === SetaPDF_Signer::PROP_TIME_OF_SIGNING) {
            $propertyValue = SetaPDF_Core_DataStructure_Date::stringToDateTime($propertyValue);
        } else {
            $propertyValue = SetaPDF_Core_Encoding::convertPdfString($propertyValue);
        }

        $signatureProperties[$property] = $propertyValue;
    }

    echo 'Signature Properties:<br/>';
    echo '<pre>';
    print_r($signatureProperties);
    echo '</pre><br /><br />';
}

if ($signatureFieldFound === false) {
    echo 'No signature field found.';
}
