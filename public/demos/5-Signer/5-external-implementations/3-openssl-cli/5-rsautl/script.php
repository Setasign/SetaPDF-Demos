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

// you need to adjust these paths to yours
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $opensslPath = 'C:\\OpenSSL\\Win64-1.1.1i\\bin\\';
} else {
    $opensslPath = '/usr/bin/';
}

// the file to sign
$fileToSign = $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf';
// create a temporary path
$tempFile = TempFileWriter::createTempPath();

// create a writer instance
$writer = new HttpWriter('signed-with-rsautl.pdf');
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

// define some variables related to the private key
$privateKey = realpath($assetsDirectory . '/certificates/setapdf-no-pw.pem');
$privateKeyPass = '';

// encode the hash in an ASN.1 structure
$digestInfo = new Asn1Element(
    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
    [
        new Asn1Element(
            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
            [
                new Asn1Element(
                    Asn1Element::OBJECT_IDENTIFIER,
                    Asn1Oid::encode(
                        Digest::getOid($module->getDigest())
                    )
                ),
                new Asn1Element(Asn1Element::NULL)
            ]
        ),
        new Asn1Element(
            Asn1Element::OCTET_STRING,
            hash($module->getDigest(), $hashData, true)
        )
    ]
);

// and write it to a temporary file
$tmpFileIn = TempFileWriter::createTempFile($digestInfo);
// prepare a temporary file for the final signature
$tmpFileOut = TempFileWriter::createTempPath();

// build the command
$cmd = $opensslPath . "openssl rsautl -sign -pkcs "
    . "-inkey " . escapeshellarg($privateKey) . ' '
    . '-passin pass:' . escapeshellarg($privateKeyPass) . ' '
    . '-in ' . escapeshellarg($tmpFileIn) . ' '
    . '-out ' . escapeshellarg($tmpFileOut);

// execute it
exec($cmd, $out, $retValue);

if ($retValue !== 0) {
    throw new SignerException(
        sprintf('An error occurs while calling OpenSSL through CLI (exit code %s).', $retValue)
    );
}

// get the signature data
$signatureValue = file_get_contents($tmpFileOut);

// pass it to the module
$module->setSignatureValue($signatureValue);

// get the final cms container
$cms = $module->getCms();
// and pass it to the main signer instance
$signer->saveSignature($tmpDocument, $cms);
