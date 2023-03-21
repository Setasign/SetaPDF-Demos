<?php

use com\setasign\SetaPDF\Demos\Signer\Appearance\OnAllPages as AppearanceOnAllPages;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';
// load the wrapper class
require_once __DIR__ . '/../../../../../classes/Signer/Appearance/OnAllPages.php';

$writer = new SetaPDF_Core_Writer_Http('several-appearances.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
//    $assetsDirectory . '/pdfs/misc/rotated/all.pdf',
    $writer
);

// create a signer instance
$signer = new SetaPDF_Signer($document);

// add a visible signature field
$field = $signer->addSignatureField(
    SetaPDF_Signer_SignatureField::DEFAULT_FIELD_NAME,
    1,
    SetaPDF_Signer_SignatureField::POSITION_RIGHT_BOTTOM,
    ['x' => -10, 'y' => 10],
    180,
    60
);

// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

// The name property is used by the appearance module as the author of the stamp annotation
$signer->setName('www.setasign.com');

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// now create the appearance module and pass the signature module along
$appearance = new SetaPDF_Signer_Signature_Appearance_Dynamic($module);
// let's create a font instance to not use standard fonts (not embedded)
$font = new SetaPDF_Core_Font_Type0_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);
// and pass it to the appearance module
$appearance->setFont($font);

// pass the appearance module wrapped into the proxy class to the signer instance
$signer->setAppearance(new AppearanceOnAllPages($appearance));

// sign the document
$signer->sign($module);
