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
$signer->setName('SetaPDF-Demo');
$signer->setReason('Testing');
$signer->setLocation('SetaPDF-Demo Environment');

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();

// pass the certificate path
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// create a font instance
$font = new SetaPDF_Core_Font_Type0_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// let's create a simple text block
$textBlock = new SetaPDF_Core_Text_Block($font, 10);
$textBlock->setWidth(200);
$textBlock->setLineHeight(11);
$textBlock->setPadding(2);
$certificateInfo = openssl_x509_parse('file://' . $certificatePath);
$text = "Signee: "
    . (isset($certificateInfo['subject']['CN']) ? $certificateInfo['subject']['CN'] : $signer->getName())
    . "\nReason: " . $signer->getReason()
    . "\nLocation: " . $signer->getLocation();
$textBlock->setText($text);

// add a signature field with the doubled height of the text block
$field = $signer->addSignatureField(
    SetaPDF_Signer_SignatureField::DEFAULT_FIELD_NAME,
    1,
    SetaPDF_Signer_SignatureField::POSITION_RIGHT_BOTTOM,
    ['x' => -40, 'y' => 50],
    $textBlock->getWidth(),
    $textBlock->getHeight() * 2
);

// set the signature field name
$signer->setSignatureFieldName($field->getQualifiedName());

$width = $textBlock->getWidth();
$height = $textBlock->getHeight() * 2;

// create a form XObject and ...
$xObject = SetaPDF_Core_XObject_Form::create($document, [0, 0, $width, $height]);
$canvas = $xObject->getCanvas();

// draw a border ...
$canvas
    ->path()
    ->setLineWidth(1)
    ->draw()
    ->setStrokingColor([1, 0, 0])
    ->rect(0, 0, $width, $height);
// draw the text block
$textBlock->draw($canvas, 0, 0);

// draw an image above the text
$image = SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/Handwritten-Signature.png');
$imageXObject = $image->toXObject($document);

$imageHeight = $height / 2;
$imageWidth = $imageXObject->getWidth($imageHeight);
$imageXObject->draw($canvas, ($width - $imageWidth) / 2, $imageHeight, null, $imageHeight);

// create a XObject appearance instance
$appearance = new SetaPDF_Signer_Signature_Appearance_XObject($xObject);

// and pass it to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
