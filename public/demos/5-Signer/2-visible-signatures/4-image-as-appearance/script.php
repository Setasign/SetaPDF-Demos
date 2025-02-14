<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Appearance\XObject;
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
    70
);

// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new PadesModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath);

// load an image and ...
$graphicImage = Image::getByPath($assetsDirectory . '/images/Handwritten-Signature.png');
// or e.g. through base64 encoded image data:
//$data = base64_decode('iVBORw0KGgoAAAANSUhEUgAABJYAAAEmCAYAAAAwZRqhAAAgAElEQVR4Xu.../w+l98Lb9eaTFwAAAABJRU5ErkJggg==');
//$graphicImage = Image::get(new \setasign\SetaPDF2\Core\Reader\StringReader($data));

// convert it to an XObject
$xObject = $graphicImage->toXObject($document);

// which can be used to initiate a XObject appearance instance
$appearance = new XObject($xObject);

// pass the appearance module to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
