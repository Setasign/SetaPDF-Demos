<?php

// load and register the autoload function
require_once('../../../../../bootstrap.php');

// list some files
$files = glob($assetsDirectory . '/pdfs/tektown/*.pdf');

foreach ($files AS $path) {
    $name = basename($path);
    $bold = (strpos($name, 'signed') !== false);

    echo '<a href="?f=' . urlencode($path) . '">';
    echo ($bold ? '<b>' : '') . htmlspecialchars($name) . ($bold ? '</b>' : '') ;
    echo '</a><br />';
}

echo '<br />';
if (!isset($_GET['f']) || !in_array($_GET['f'], $files, true)) {
    die();
}

$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);
$terminalFields = $document->getCatalog()->getAcroForm()->getTerminalFieldsObjects();

$signatureFieldFound = false;

foreach ($terminalFields AS $fieldData) {
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

    echo '<br /><br />';
}

if ($signatureFieldFound === false) {
    echo 'No signature field found.';
}