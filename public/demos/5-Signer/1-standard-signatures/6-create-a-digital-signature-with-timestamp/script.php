<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\Timestamp\Module\Rfc3161\Curl as CurlTimestampModule;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new HttpWriter('signed-and-timestamped.pdf');
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new Signer($document);
// add a signature field
$field = $signer->addSignatureField();
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
$module->setPrivateKey('file://' . $certificatePath, '');

// use the timestamp authority you like
$url = 'https://freetsa.org/tsr';

// create a timestamp module instance
$tsModule = new CurlTimestampModule($url);
// pass the timestamp module instance to the signer
$signer->setTimestampModule($tsModule);

// because the timestamp will make it into the signature, we need to increase the reserved space
$signer->setSignatureContentLength(15000);

// sign the document with the module
$signer->sign($module);
