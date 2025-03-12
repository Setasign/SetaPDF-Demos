<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new HttpWriter('signed.pdf');
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
// or its content
//$module->setCertificate(file_get_contents($certificatePath));
// or a certificate instance
//$certificate = \setasign\SetaPDF2\Signer\X509\Certificate::fromFileOrString($certificatePath);
//$module->setCertificate($certificate);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
