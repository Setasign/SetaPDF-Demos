<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$file = $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf';
//$file = $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf';

$fh = fopen($file, 'rb');
$reader = new SetaPDF_Core_Reader_Stream($fh);
$document = SetaPDF_Core_Document::load($reader);

$fieldNames = SetaPDF_Signer_ValidationRelatedInfo_Collector::getSignatureFieldNames($document);

$fieldNameId = displaySelect('Signature-Field name', $fieldNames);
$fieldName = $fieldNames[$fieldNameId];

$integrityResult = SetaPDF_Signer_ValidationRelatedInfo_IntegrityResult::create($document, $fieldName);
$field = $integrityResult->getField();
$value = $field->getValue();

$byteRange = $value->getValue('ByteRange')->toPhp();

// THIS LOGIC TAKES THE PREVIOUS REVISION... MAY NOT BE WHAT YOU WOULD EXPECT
//// jump to first offset
//$frameSize = 1000;
//$i = 0;
//do {
//    $offset = $frameSize + (($i * $frameSize) - ($i * 10));
//    $reader->reset($byteRange[1] - $offset, $frameSize);
//    $buffer = $reader->getBuffer();
//    if ($i++ > 1000) {
//        break;
//    }
//} while (($pos = strrpos($buffer, '%%EOF')) === false);
//
//if ($pos === false) {
//    echo "Unable to extract previous revision.";
//    die();
//}
//
//// check if the revision is the first one for linearization
//$reader->reset($byteRange[1] - $offset + $pos - 30, 30);
//$buffer = $reader->getBuffer();
//
//if (preg_match('/startxref[\r\n]*0[\r\n]*/', $buffer)) {
//    $length = $byteRange[2] + $byteRange[3];
//} else {
//    $length = $byteRange[1] - $offset + $pos + 5 /* %%EOF */;
//    // search for additional line breaks
//    fseek($fh, $length);
//    $nl = fread($fh, 2);
//    if ($nl[0] === "\r") {
//        $length++;
//        if ($nl[1] === "\n") {
//            $length++;
//        }
//    } elseif ($nl[0] === "\n") {
//        $length++;
//    }
//}

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
