<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\Type0\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Appearance\Dynamic;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\SignatureField;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new HttpWriter('visible-signature.pdf', true);
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new Signer($document);
// add a visible signature field
$field = $signer->addSignatureField(
    SignatureField::DEFAULT_FIELD_NAME,
    1,
    SignatureField::POSITION_RIGHT_TOP,
    ['x' => -160, 'y' => -100],
    180,
    60
);

// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// now create the appearance module and pass the signature module along
$appearance = new Dynamic($module);
// let's create a font instance to not use standard fonts (not embedded)
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);
// and pass it to the appearance module
$appearance->setFont($font);

// set the visibility of the config labels
$appearance->setShow(Dynamic::CONFIG_LABELS, true);

// change the date format
$appearance->setShowFormat(Dynamic::CONFIG_DATE, 'd.m.Y H:i:s');

// change the show templates to german
$appearance->setShowTpl(Dynamic::CONFIG_NAME, 'Digital signiert durch: %s');
$appearance->setShowTpl(Dynamic::CONFIG_REASON, 'Grund: %s');
$appearance->setShowTpl(Dynamic::CONFIG_LOCATION, 'URL: %s');
$appearance->setShowTpl(Dynamic::CONFIG_DATE, 'Datum: %s');

// use a photo for the signature
$appDocument = Document::loadByFilename($assetsDirectory . '/pdfs/misc/Passport-Photo.pdf');
$pageXObject = $appDocument->getCatalog()->getPages()->getPage(1)->toXObject($document);

// set the photo xObject as graphic
$appearance->setGraphic($pageXObject);

// pass the appearance module to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
