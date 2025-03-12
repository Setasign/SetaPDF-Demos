<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\Type0\Subset;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Appearance\Dynamic;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\SignatureField;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$pdfOrPng = displaySelect(
    'Use PDF pages or PNG images as appearances:',
    ['pdf' => 'PDF pages', 'png' => 'PNG images'],
    false
);

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

if ($pdfOrPng === 'pdf') {
    // load a PDF for the background appearance
    $bgDocument = Document::loadByFilename($assetsDirectory . '/pdfs/camtown/Logo.pdf');
    // convert the first page to a XObject
    $xObject = $bgDocument
        ->getCatalog()
        ->getPages()
        ->getPage(1)
        ->toXObject($document, PageBoundaries::ART_BOX);
    // add it to the appearance
    $appearance->setBackgroundLogo($xObject, .3);

    // load a PDF for the graphic appearance
    $graphicDocument = Document::loadByFilename($assetsDirectory . '/pdfs/misc/Handwritten-Signature.pdf');
    // convert the first page to a XObject
    $xObject = $graphicDocument
        ->getCatalog()
        ->getPages()
        ->getPage(1)
        ->toXObject($document, PageBoundaries::ART_BOX);
    // add it to the appearance
    $appearance->setGraphic($xObject);

} elseif ($pdfOrPng === 'png') {
    // load a PNG image for the background appearance
    $bgImage = Image::getByPath($assetsDirectory . '/pdfs/camtown/Logo.png');
    $xObject = $bgImage->toXObject($document);
    // add it to the appearance
    $appearance->setBackgroundLogo($xObject, .3);

    // load a PNG image for the graphic appearance
    $graphicImage = Image::getByPath($assetsDirectory . '/images/Handwritten-Signature.png');
    $xObject = $graphicImage->toXObject($document);
    // add it to the appearance
    $appearance->setGraphic($xObject);
}

// pass the appearance module to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
