<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new SetaPDF_Core_Writer_Http('signed.pdf');
$document = SetaPDF_Core_Document::loadByFilename(
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
// or its content
//$module->setCertificate(file_get_contents($certificatePath));
// or a certificate instance
//$certificate = SetaPDF_Signer_X509_Certificate::fromFileOrString($certificatePath);
//$module->setCertificate($certificate);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
