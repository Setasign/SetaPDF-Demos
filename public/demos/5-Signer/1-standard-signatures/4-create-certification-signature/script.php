<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$certificationLevel = (int)displaySelect('Certification Level:', [
    Signer::CERTIFICATION_LEVEL_NONE =>
        '\setasign\SetaPDF2\Signer\Signer::CERTIFICATION_LEVEL_NONE (default)',
    Signer::CERTIFICATION_LEVEL_NO_CHANGES_ALLOWED =>
        '\setasign\SetaPDF2\Signer\Signer::CERTIFICATION_LEVEL_NO_CHANGES_ALLOWED',
    Signer::CERTIFICATION_LEVEL_FORM_FILLING =>
        '\setasign\SetaPDF2\Signer\Signer::CERTIFICATION_LEVEL_FORM_FILLING',
    Signer::CERTIFICATION_LEVEL_FORM_FILLING_AND_ANNOTATIONS =>
        '\setasign\SetaPDF2\Signer\Signer::CERTIFICATION_LEVEL_FORM_FILLING_AND_ANNOTATIONS'
], false);

$writer = new HttpWriter('certified.pdf');
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

// set the certification level
$signer->setCertificationLevel($certificationLevel);

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';
// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
