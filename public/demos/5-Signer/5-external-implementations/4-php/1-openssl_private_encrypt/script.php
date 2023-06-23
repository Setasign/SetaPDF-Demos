<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// the file to sign
$fileToSign = $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf';
// create a temporary path
$tempFile = \SetaPDF_Core_Writer_TempFile::createTempPath();

// create a writer instance
$writer = new \SetaPDF_Core_Writer_Http('signed-with-php-openssl.pdf');
// create the document instance
$document = \SetaPDF_Core_Document::loadByFilename($fileToSign, $writer);

// create the signer instance
$signer = new SetaPDF_Signer($document);

// let's use the PAdES modul and configure it
$module = new SetaPDF_Signer_Signature_Module_Pades();
$module->setDigest(SetaPDF_Signer_Digest::SHA_256);
$module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');

// create a temporary version which represents the data which should get signed
$tmpDocument = $signer->preSign(new \SetaPDF_Core_Writer_File($tempFile), $module);

// get the hash data from the module
$hashData = $module->getDataToSign($tmpDocument->getHashFile());
// hash the data
$hash = hash($module->getDigest(), $hashData, true);

// let's sign only the hash, so we create the ASN.1 container manually
$digestInfo = new SetaPDF_Signer_Asn1_Element(
    SetaPDF_Signer_Asn1_Element::SEQUENCE | SetaPDF_Signer_Asn1_Element::IS_CONSTRUCTED, '',
    [
        new SetaPDF_Signer_Asn1_Element(
            SetaPDF_Signer_Asn1_Element::SEQUENCE | SetaPDF_Signer_Asn1_Element::IS_CONSTRUCTED, '',
            [
                new SetaPDF_Signer_Asn1_Element(
                    SetaPDF_Signer_Asn1_Element::OBJECT_IDENTIFIER,
                    SetaPDF_Signer_Asn1_Oid::encode(
                        SetaPDF_Signer_Digest::getOid($module->getDigest())
                    )
                ),
                new SetaPDF_Signer_Asn1_Element(SetaPDF_Signer_Asn1_Element::NULL)
            ]
        ),
        new SetaPDF_Signer_Asn1_Element(
            SetaPDF_Signer_Asn1_Element::OCTET_STRING,
            $hash
        )
    ]
);

// define some variables related to the private key
$privateKey = realpath($assetsDirectory . '/certificates/setapdf-no-pw.pem');
$privateKeyPass = '';

$pkey = openssl_pkey_get_private(file_get_contents($privateKey), $privateKeyPass);

if (false === @openssl_private_encrypt($digestInfo, $signatureValue, $pkey)) {
    $lastError = error_get_last();
    throw new SetaPDF_Signer_Exception(
        'An OpenSSL error occured during signature process' .
        (isset($lastError['message']) ? ': ' . $lastError['message'] : '') . '.'
    );
}

// pass the result to the module
$module->setSignatureValue($signatureValue);

$cms = $module->getCms();
$signer->saveSignature($tmpDocument, $cms);
