<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\SecHandler\SecHandlerInterface;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new HttpWriter('topsecret.pdf');
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-up=topsecret,op=owner.pdf',
    $writer
);

// let's create an authentication callback
$authCallback = static function(SecHandlerInterface $secHandler) {
    $secHandler->auth('owner');
};

// create a signer instance and pass the callback
$signer = new Signer($document, $authCallback);

// add a signature field
$field = $signer->addSignatureField();
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
