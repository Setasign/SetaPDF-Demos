<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$file = $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf';

$fh = fopen($file, 'rb');
$reader = new SetaPDF_Core_Reader_Stream($fh);
$document = SetaPDF_Core_Document::load($reader);

$fieldNames = SetaPDF_Signer_ValidationRelatedInfo_Collector::getSignatureFieldNames($document);

$fieldNameId = displaySelect('Signature field name:', $fieldNames);
$fieldName = $fieldNames[$fieldNameId];

$integrityResult = SetaPDF_Signer_ValidationRelatedInfo_IntegrityResult::create($document, $fieldName);
$field = $integrityResult->getField();
$value = $field->getValue();

$byteRange = $value->getValue('ByteRange')->toPhp();
$length = $byteRange[2] + $byteRange[3];

fseek($fh, 0);
$out = fopen('php://temp', 'r+b');
stream_copy_to_stream($fh, $out, $length);
fseek($out, 0);

$writer = new SetaPDF_Core_Writer_Http('result.pdf');
$writer->copy($out);
$writer->finish();

fclose($fh);
fclose($out);
