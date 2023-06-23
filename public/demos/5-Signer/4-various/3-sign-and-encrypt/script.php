<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new \SetaPDF_Core_Writer_Http('encrypted-and-signed.pdf');
$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

$ownerPassword = 'AVerySecretOwnerPassword';
$userPassword = 'ANotherVerySecretUserPassword';

// all permissions but printing
$permissions = \SetaPDF_Core_SecHandler::PERM_ACCESSIBILITY
    | \SetaPDF_Core_SecHandler::PERM_ANNOT
    | \SetaPDF_Core_SecHandler::PERM_ASSEMBLE
    | \SetaPDF_Core_SecHandler::PERM_COPY
    | \SetaPDF_Core_SecHandler::PERM_MODIFY
    | \SetaPDF_Core_SecHandler::PERM_FILL_FORM
;

// create a security handler
$secHandler = \SetaPDF_Core_SecHandler_Standard_Aes256::factory(
    $document, $ownerPassword, $userPassword, $permissions
);
// and pass it to the document instance
$document->setSecHandler($secHandler);

// let's create an authentication callback
$authCallback = static function(\SetaPDF_Core_SecHandler_SecHandlerInterface $secHandler) use ($ownerPassword) {
    $secHandler->auth($ownerPassword);
};

// create a signer instance and pass the callback
$signer = new SetaPDF_Signer($document, $authCallback);

// add a signature field
$field = $signer->addSignatureField();
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create a signature module
$module = new SetaPDF_Signer_Signature_Module_Pades();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
