<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a temporary writer
$tempWriter = new SetaPDF_Core_Writer_String();

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $tempWriter
);

// create a signer instance
$signer = new SetaPDF_Signer($document);
// add a visible signature field
$field = $signer->addSignatureField(
    SetaPDF_Signer_SignatureField::DEFAULT_FIELD_NAME,
    1,
    SetaPDF_Signer_SignatureField::POSITION_RIGHT_TOP,
    ['x' => -160, 'y' => -100],
    180,
    70
);
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
$module->setPrivateKey('file://' . $certificatePath, '');

// creat an appearance module instance
$appearance = new SetaPDF_Signer_Signature_Appearance_Dynamic($module);
// pass it to the signer instance
$signer->setAppearance($appearance);

// sign the document with the module
$signer->sign($module);


// now simply re-start the process


// create the final writer
$writer = new SetaPDF_Core_Writer_Http('several-signatures.pdf');

// create a new document instance based on the temporary result
$document = SetaPDF_Core_Document::loadByString($tempWriter, $writer);

// create a signer instance
$signer = new SetaPDF_Signer($document);
// add a visible signature field
$field = $signer->addSignatureField(
    SetaPDF_Signer_SignatureField::DEFAULT_FIELD_NAME,
    1,
    SetaPDF_Signer_SignatureField::POSITION_RIGHT_TOP,
    ['x' => -160, 'y' => -200],
    180,
    70
);
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
$module->setPrivateKey('file://' . $certificatePath, '');

// creat an appearance module instance
$appearance = new SetaPDF_Signer_Signature_Appearance_Dynamic($module);
// pass it to the signer instance
$signer->setAppearance($appearance);

// sign the document and wriote the final document to the final writer
$signer->sign($module);
