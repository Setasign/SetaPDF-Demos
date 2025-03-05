<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Type\Dictionary\DictionaryHelper;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfStream;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report-signed.pdf',
];

$path = displayFiles($files, false);

$writer = new HttpWriter(basename($path));
$document = Document::loadByFilename($path, $writer);

// get all terminal fields
$terminalFields = $document->getCatalog()->getAcroForm()->getTerminalFieldsObjects();

// iterate over the fields
foreach ($terminalFields AS $fieldData) {
    /** @var PdfDictionary $fieldData */
    $fieldData = $fieldData->ensure();

    // ensure that the field is a signature field
    $ft = DictionaryHelper::resolveAttribute($fieldData, 'FT');
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

    /** @var PdfDictionary $ap */
    $ap = $ap->ensure();
    $n = $ap->getValue('N');
    if ($n) {
        /** @var PdfStream $n */
        $n = $n->ensure();
        $n->setStream('%% Blank');
    }
}

// done
$document->save(false)->finish();