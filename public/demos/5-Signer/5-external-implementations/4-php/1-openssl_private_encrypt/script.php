<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\FileWriter;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\Writer\TempFileWriter;
use setasign\SetaPDF2\Signer\Asn1\Element as Asn1Element;
use setasign\SetaPDF2\Signer\Asn1\Oid as Asn1Oid;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\Exception as SignerException;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// the file to sign
$fileToSign = $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf';
// create a temporary path
$tempFile = TempFileWriter::createTempPath();

// create a writer instance
$writer = new HttpWriter('signed-with-php-openssl.pdf');
// create the document instance
$document = Document::loadByFilename($fileToSign, $writer);

// create the signer instance
$signer = new Signer($document);

// let's use the PAdES modul and configure it
$module = new PadesModule();
$module->setDigest(Digest::SHA_256);
$module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');

// create a temporary version which represents the data which should get signed
$tmpDocument = $signer->preSign(new FileWriter($tempFile), $module);

// get the hash data from the module
$hashData = $module->getDataToSign($tmpDocument->getHashFile());
// hash the data
$hash = hash($module->getDigest(), $hashData, true);

// let's sign only the hash, so we create the ASN.1 container manually
$digestInfo = new Asn1Element(
    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
    [
        new Asn1Element(
            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
            [
                new Asn1Element(
                    Asn1Element::OBJECT_IDENTIFIER,
                    Asn1Oid::encode(Digest::getOid($module->getDigest()))
                ),
                new Asn1Element(Asn1Element::NULL)
            ]
        ),
        new Asn1Element(
            Asn1Element::OCTET_STRING,
            $hash
        )
    ]
);

// define some variables related to the private key
$privateKey = realpath($assetsDirectory . '/certificates/setapdf-no-pw.pem');
$privateKeyPass = '';

$pkey = \openssl_pkey_get_private(file_get_contents($privateKey), $privateKeyPass);

if (false === @\openssl_private_encrypt($digestInfo, $signatureValue, $pkey)) {
    $lastError = error_get_last();
    throw new SignerException(
        'An OpenSSL error occurred during signature process' .
        (isset($lastError['message']) ? ': ' . $lastError['message'] : '') . '.'
    );
}

// pass the result to the module
$module->setSignatureValue($signatureValue);

$cms = $module->getCms();
$signer->saveSignature($tmpDocument, $cms);
