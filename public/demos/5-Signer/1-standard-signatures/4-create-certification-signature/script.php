<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$certificationLevel = (int)displaySelect('Certification Level:', [
    SetaPDF_Signer::CERTIFICATION_LEVEL_NONE =>
        'SetaPDF_Signer::CERTIFICATION_LEVEL_NONE (default)',
    SetaPDF_Signer::CERTIFICATION_LEVEL_NO_CHANGES_ALLOWED =>
        'SetaPDF_Signer::CERTIFICATION_LEVEL_NO_CHANGES_ALLOWED',
    SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING =>
        'SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING',
    SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING_AND_ANNOTATIONS =>
        'SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING_AND_ANNOTATIONS'
], false);

$writer = new SetaPDF_Core_Writer_Http('certified.pdf');
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

// set the certification level
$signer->setCertificationLevel($certificationLevel);

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';
// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
