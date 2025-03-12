<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\Writer\StringWriter;
use setasign\SetaPDF2\Signer\Signature\Appearance\Dynamic;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\SignatureField;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a temporary writer
$tempWriter = new StringWriter();

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $tempWriter
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
    70
);
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
$module->setPrivateKey('file://' . $certificatePath, '');

// creat an appearance module instance
$appearance = new Dynamic($module);
// pass it to the signer instance
$signer->setAppearance($appearance);

// sign the document with the module
$signer->sign($module);


// now simply re-start the process


// create the final writer
$writer = new HttpWriter('several-signatures.pdf', true);

// create a new document instance based on the temporary result
$document = Document::loadByString($tempWriter, $writer);

// create a signer instance
$signer = new Signer($document);
// add a visible signature field
$field = $signer->addSignatureField(
    SignatureField::DEFAULT_FIELD_NAME,
    1,
    SignatureField::POSITION_RIGHT_TOP,
    ['x' => -160, 'y' => -200],
    180,
    70
);
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
$module->setPrivateKey('file://' . $certificatePath, '');

// creat an appearance module instance
$appearance = new Dynamic($module);
// pass it to the signer instance
$signer->setAppearance($appearance);

// sign the document and write the final document to the final writer
$signer->sign($module);
