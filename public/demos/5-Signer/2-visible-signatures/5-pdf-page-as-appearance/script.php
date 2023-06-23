<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new \SetaPDF_Core_Writer_Http('visible-signature.pdf', true);
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
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
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// load the PDF document and ...
$graphicDocument = \SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/misc/Handwritten-Signature.pdf');
// convert the first page to a XObject
$xObject = $graphicDocument
    ->getCatalog()
    ->getPages()
    ->getPage(1)
    ->toXObject($document, \SetaPDF_Core_PageBoundaries::ART_BOX);

// which can be used to initiate a XObject appearance instance
$appearance = new SetaPDF_Signer_Signature_Appearance_XObject($xObject);

// pass the appearance module to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
