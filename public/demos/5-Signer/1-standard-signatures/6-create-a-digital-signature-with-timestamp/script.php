<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new \SetaPDF_Core_Writer_Http('signed-and-timestamped.pdf');
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new SetaPDF_Signer($document);
// add a signature field
$field = $signer->addSignatureField();
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
$module->setPrivateKey('file://' . $certificatePath, '');

// use the timestamp authority you like
$url = 'https://freetsa.org/tsr';

// create a timestamp module instance
$tsModule = new SetaPDF_Signer_Timestamp_Module_Rfc3161_Curl($url);
// pass the timestamp module instance to the signer
$signer->setTimestampModule($tsModule);

// because the timestamp will make it into the signature, we need to increase the reserved space
$signer->setSignatureContentLength(15000);

// sign the document with the module
$signer->sign($module);
