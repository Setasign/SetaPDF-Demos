<?php

use com\setasign\SetaPDF\Demos\Signer\Module\Signature\OpenSslPrivateEncryptModule;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\Writer\TempFileWriter;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';
// load the module class
require_once __DIR__ . '/../../../../../../classes/Signer/Module/Signature/OpenSslPrivateEncryptModule.php';

// the file to sign
$fileToSign = $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf';
// create a temporary path
$tempFile = TempFileWriter::createTempPath();

// create a writer instance
$writer = new HttpWriter('signed-with-php-openssl.pdf');
// create the document instance
$document = Document::loadByFilename($fileToSign, $writer);

// create the signer instance
$signer = new Signer($document);

// let's use the PAdES modul and configure it
$module = new OpenSslPrivateEncryptModule();
$module->setDigest(Digest::SHA_256);
$module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');

$privateKey = \openssl_pkey_get_private('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem', '');
$module->setPrivateKey($privateKey);

$signer->sign($module);
