<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new SetaPDF_Core_Writer_Http('timestamped.pdf');
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new SetaPDF_Signer($document);
// add a signature field
$field = $signer->addSignatureField('Timestamp');
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

// use the timestamp authority you like
$url = 'https://freetsa.org/tsr';

$tsModule = new SetaPDF_Signer_Timestamp_Module_Rfc3161_Curl($url);

// if you need to authenticate with a password:
//$username = 'yourUserName';
//$password = 'yourSecretPassword';
//
//$tsModule->setCurlOption(CURLOPT_USERPWD, $username . ':' . $password);

// if you need to authenticate with a certificate
//$certFile = 'client-certificate.pem';
//$certPassword = 'password';
//
//$tsModule->setCurlOption([
//    CURLOPT_SSLCERT => $certFile,
//    CURLOPT_SSLCERTPASSWD => $certPassword
//]);

// pass the timestamp module instance to the signer
$signer->setTimestampModule($tsModule);
// timestamp the document
$signer->timestamp();
