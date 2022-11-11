<?php

use com\setasign\SetaPDF\Demos\Signer\Module\Signature\OpenSslPrivateEncryptModule;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';
// load the module class
require_once __DIR__ . '/../../../../../../classes/Signer/Module/Signature/OpenSslPrivateEncryptModule.php';

// the file to sign
$fileToSign = $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf';
// create a temporary path
$tempFile = SetaPDF_Core_Writer_TempFile::createTempPath();

// create a writer instance
$writer = new SetaPDF_Core_Writer_Http('signed-with-php-openssl.pdf');
// create the document instance
$document = SetaPDF_Core_Document::loadByFilename($fileToSign, $writer);

// create the signer instance
$signer = new SetaPDF_Signer($document);

// let's use the PAdES modul and configure it
$module = new OpenSslPrivateEncryptModule();
$module->setDigest(SetaPDF_Signer_Digest::SHA_256);
$module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');

$privateKey = openssl_pkey_get_private('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem', '');
$module->setPrivateKey($privateKey);

$signer->sign($module);
