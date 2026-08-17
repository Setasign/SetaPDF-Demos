<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Reader\StreamReader;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\ValidationRelatedInfo\IntegrityResult;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$file = $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf';

$fh = fopen($file, 'rb');
$reader = new StreamReader($fh);
$document = Document::load($reader);

$fieldNames = Signer::getSignatureFieldNames($document);
// let's filter only used signature fields
$fieldNames = array_filter($fieldNames, static function($fieldName) use ($document) {
    $integrityResult = IntegrityResult::create($document, $fieldName);
    return $integrityResult !== IntegrityResult::STATUS_NOT_SIGNED;
});

$fieldNameId = displaySelect('Signature field name:', $fieldNames);
$fieldName = $fieldNames[$fieldNameId];

$integrityResult = IntegrityResult::create($document, $fieldName);

$writer = new HttpWriter('result.pdf');
IntegrityResult::extractSignedVersion(
    $integrityResult,
    $reader,
    $writer
);

fclose($fh);