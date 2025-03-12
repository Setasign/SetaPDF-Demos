<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\Timestamp\Module\Rfc3161\Curl as CurlTimestampModule;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new HttpWriter('timestamped.pdf');
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new Signer($document);
// add a signature field
$field = $signer->addSignatureField('Timestamp');
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

// use the timestamp authority you like
$url = 'https://freetsa.org/tsr';

$tsModule = new CurlTimestampModule($url);

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
