<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$pdfOrPng = displaySelect(
    'Use PDF pages or PNG images as appearances:',
    ['pdf' => 'PDF pages', 'png' => 'PNG images'],
    false
);

$writer = new \SetaPDF_Core_Writer_Http('visible-signature.pdf', true);
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new \SetaPDF_Signer($document);
// add a visible signature field
$field = $signer->addSignatureField(
    \SetaPDF_Signer_SignatureField::DEFAULT_FIELD_NAME,
    1,
    \SetaPDF_Signer_SignatureField::POSITION_RIGHT_TOP,
    ['x' => -160, 'y' => -100],
    180,
    60
);

// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new \SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// now create the appearance module and pass the signature module along
$appearance = new \SetaPDF_Signer_Signature_Appearance_Dynamic($module);
// let's create a font instance to not use standard fonts (not embedded)
$font = new \SetaPDF_Core_Font_Type0_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);
// and pass it to the appearance module
$appearance->setFont($font);

if ($pdfOrPng === 'pdf') {
    // load a PDF for the background appearance
    $bgDocument = \SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/camtown/Logo.pdf');
    // convert the first page to a XObject
    $xObject = $bgDocument
        ->getCatalog()
        ->getPages()
        ->getPage(1)
        ->toXObject($document, \SetaPDF_Core_PageBoundaries::ART_BOX);
    // add it to the appearance
    $appearance->setBackgroundLogo($xObject, .3);

    // load a PDF for the graphic appearance
    $graphicDocument = \SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/misc/Handwritten-Signature.pdf');
    // convert the first page to a XObject
    $xObject = $graphicDocument
        ->getCatalog()
        ->getPages()
        ->getPage(1)
        ->toXObject($document, \SetaPDF_Core_PageBoundaries::ART_BOX);
    // add it to the appearance
    $appearance->setGraphic($xObject);

} elseif ($pdfOrPng === 'png') {
    // load a PNG image for the background appearance
    $bgImage = \SetaPDF_Core_Image::getByPath($assetsDirectory . '/pdfs/camtown/Logo.png');
    $xObject = $bgImage->toXObject($document);
    // add it to the appearance
    $appearance->setBackgroundLogo($xObject, .3);

    // load a PNG image for the graphic appearance
    $graphicImage = \SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/Handwritten-Signature.png');
    $xObject = $graphicImage->toXObject($document);
    // add it to the appearance
    $appearance->setGraphic($xObject);
}

// pass the appearance module to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
