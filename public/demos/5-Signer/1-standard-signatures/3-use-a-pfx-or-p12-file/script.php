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

$pkcs12 = [];
$pfxRead = openssl_pkcs12_read(
    file_get_contents($assetsDirectory . '/certificates/setapdf-pw-is-setapdf.pfx'),
    $pkcs12,
    'setapdf'
);

// error handling
if ($pfxRead === false) {
    throw new Exception('The PFX file could not be read.');
}


// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the certificate ...
$module->setCertificate($pkcs12['cert']);
// ...and private key to the module
$module->setPrivateKey($pkcs12['pkey']);

// pass extra certificates if included in the PFX file
if (isset($pkcs12['extracerts']) && count($pkcs12['extracerts'])) {
    $module->setExtraCertificates($pkcs12['extracerts']);
}

// sign the document with the module
$signer->sign($module);
