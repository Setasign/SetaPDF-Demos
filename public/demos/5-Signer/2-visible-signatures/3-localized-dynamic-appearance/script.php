<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new SetaPDF_Core_Writer_Http('visible-signature.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename(
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
    60
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

// now create the appearance module and pass the signature module along
$appearance = new SetaPDF_Signer_Signature_Appearance_Dynamic($module);
// let's create a font instance to not use standard fonts (not embedded)
$font = new SetaPDF_Core_Font_Type0_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);
// and pass it to the appearance module
$appearance->setFont($font);

// set the visibility of the config labels
$appearance->setShow(SetaPDF_Signer_Signature_Appearance_Dynamic::CONFIG_LABELS, true);

// change the date format
$appearance->setShowFormat(SetaPDF_Signer_Signature_Appearance_Dynamic::CONFIG_DATE, 'd.m.Y H:i:s');

// change the show templates to german
$appearance->setShowTpl(SetaPDF_Signer_Signature_Appearance_Dynamic::CONFIG_NAME, 'Digital signiert durch: %s');
$appearance->setShowTpl(SetaPDF_Signer_Signature_Appearance_Dynamic::CONFIG_REASON, 'Grund: %s');
$appearance->setShowTpl(SetaPDF_Signer_Signature_Appearance_Dynamic::CONFIG_LOCATION, 'URL: %s');
$appearance->setShowTpl(SetaPDF_Signer_Signature_Appearance_Dynamic::CONFIG_DATE, 'Datum: %s');

// use a photo for the signature
$appDocument = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/misc/Passport-Photo.pdf');
$pageXObject = $appDocument->getCatalog()->getPages()->getPage(1)->toXObject($document);

// set the photo xObject as graphic
$appearance->setGraphic($pageXObject);

// pass the appearance module to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
