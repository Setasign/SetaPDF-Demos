<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report-signed.pdf',
];

$path = displayFiles($files, false);

$writer = new SetaPDF_Core_Writer_Http(basename($path));
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

// get all terminal fields
$terminalFields = $document->getCatalog()->getAcroForm()->getTerminalFieldsObjects();

// iterate over the fields
foreach ($terminalFields AS $fieldData) {
    /** @var SetaPDF_Core_Type_Dictionary $fieldData */
    $fieldData = $fieldData->ensure();

    // ensure that the field is a signature field
    $ft = SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute($fieldData, 'FT');
    if (!$ft || $ft->getValue() !== 'Sig') {
        continue;
    }

    // if no value is set (not signed) continue
    if (!$fieldData->offsetExists('V')) {
        continue;
    }

    // unset the value
    $fieldData->offsetUnset('V');

    // clear the appearance stream.
    $ap = $fieldData->getValue('AP');
    if (!$ap) {
        continue;
    }

    /** @var SetaPDF_Core_Type_Dictionary $ap */
    $ap = $ap->ensure();
    $n = $ap->getValue('N');
    if ($n) {
        /** @var SetaPDF_Core_Type_Stream $n */
        $n = $n->ensure();
        $n->setStream('%% Blank');
    }
}

// done
$document->save(false)->finish();