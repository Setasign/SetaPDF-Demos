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

// add a signature field with the doubled height of the text block
$field = $signer->addSignatureField(
    SetaPDF_Signer_SignatureField::DEFAULT_FIELD_NAME,
    1,
    SetaPDF_Signer_SignatureField::POSITION_RIGHT_BOTTOM,
    ['x' => -40, 'y' => 50],
    250,
    70
);
// set the signature field name
$signer->setSignatureFieldName($field->getQualifiedName());

$width = $field->getWidth();
$height = $field->getHeight();

// create a form XObject and ...
$xObject = \SetaPDF_Core_XObject_Form::create($document, [0, 0, $width, $height]);
$canvas = $xObject->getCanvas();

// add a seal on the left side
$sealImage = \SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/seal.png');
$sealImageXObject = $sealImage->toXObject($document);
$sealImageXObject->draw($canvas, 0, 0, null, $height);
$sealWidth = $sealImageXObject->getWidth($height);

// add a QR-Code image to the right
$qrImage = \SetaPDF_Core_Image::getByPath($assetsDirectory . '/images/qr.png');
$qrImageXObject = $qrImage->toXObject($document);
$qrWidth = $qrImageXObject->getWidth($height);
$qrImageXObject->draw($canvas, $width - $qrWidth, 0, null, $height);

// now create a text between both images

// create a font instance
$font = new \SetaPDF_Core_Font_Type0_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);
// let's create a simple text block
$textBlock = new \SetaPDF_Core_Text_Block($font, 10);
$textBlock->setTextWidth($width - $sealWidth - $qrWidth);
$textBlock->setLineHeight(11);
$textBlock->setPadding(2);
$textBlock->setAlign(\SetaPDF_Core_Text::ALIGN_CENTER);
$certificateInfo = openssl_x509_parse('file://' . $certificatePath);
$text = "Signee: "
    . (isset($certificateInfo['subject']['CN']) ? $certificateInfo['subject']['CN'] : $signer->getName())
    . "\nReason: " . $signer->getReason()
    . "\nLocation: " . $signer->getLocation();
$textBlock->setText($text);

// draw it into the center
$textBlock->draw($canvas, $sealWidth, $height / 2 - $textBlock->getHeight() / 2);

// create a XObject appearance instance
$appearance = new SetaPDF_Signer_Signature_Appearance_XObject($xObject);

// and pass it to the signer instance
$signer->setAppearance($appearance);

// sign the document
$signer->sign($module);
