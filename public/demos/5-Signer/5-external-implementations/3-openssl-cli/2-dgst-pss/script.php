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
$writer = new HttpWriter('signed-with-dgst.pdf');
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

// for PSS we need to update the SignatureAlgorithmIdentifier as defined here:
// https://tools.ietf.org/html/rfc4055#section-3.1
$cms = $module->getCms();

$saltLength = 256 / 8;
switch ($module->getDigest()) {
    case Digest::SHA_384:
        $saltLength = 384 / 8;
        break;
    case Digest::SHA_512:
        $saltLength = 512 / 8;
        break;
}

$signatureAlgorithmIdentifier = Asn1Element::findByPath('1/0/4/0/4', $cms);
$signatureAlgorithmIdentifier->getChild(0)->setValue(Asn1Oid::encode("1.2.840.113549.1.1.10"));
$signatureAlgorithmIdentifier->removeChild($signatureAlgorithmIdentifier->getChild(1));
$signatureAlgorithmIdentifier->addChild(new Asn1Element(
    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
    [
        new Asn1Element(
            Asn1Element::TAG_CLASS_CONTEXT_SPECIFIC | Asn1Element::IS_CONSTRUCTED, '',
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
                )
            ]
        ),
        new Asn1Element(
            Asn1Element::TAG_CLASS_CONTEXT_SPECIFIC | Asn1Element::IS_CONSTRUCTED | "\x01", '',
            [
                new Asn1Element(
                    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
                    [
                        new Asn1Element(
                            Asn1Element::OBJECT_IDENTIFIER,
                            Asn1Oid::encode('1.2.840.113549.1.1.8')
                        ),
                        new Asn1Element(
                            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
                            [
                                new Asn1Element(
                                    Asn1Element::OBJECT_IDENTIFIER,
                                    Asn1Oid::encode(Digest::getOid($module->getDigest()))
                                ),
                                new Asn1Element(Asn1Element::NULL)
                            ]
                        )
                    ]
                )
            ]
        ),
        new Asn1Element(
            Asn1Element::TAG_CLASS_CONTEXT_SPECIFIC | Asn1Element::IS_CONSTRUCTED | "\x02",
            '',
            [
                new Asn1Element(Asn1Element::INTEGER, \chr($saltLength))
            ]
        )
    ]
));

// get the hash data from the module
$hashData = $module->getDataToSign($tmpDocument->getHashFile());

// define some variables related to the private key
$privateKey = realpath($assetsDirectory . '/certificates/setapdf-no-pw.pem');
$privateKeyPass = '';

// create a temporary file with the data to sign
$tmpFileIn = TempFileWriter::createTempFile($hashData);
// prepare a temporary file for the final signature
$tmpFileOut = TempFileWriter::createTempPath();

// build the command
$cmd = $opensslPath . 'openssl dgst '
    . '-' . $module->getDigest() . ' '
    . '-sigopt rsa_padding_mode:pss -sigopt rsa_pss_saltlen:' . escapeshellarg($saltLength) . ' '
    . '-binary '
    . "-sign " . escapeshellarg($privateKey) . ' '
    . '-passin pass:' . escapeshellarg($privateKeyPass) . ' '
    . '-out ' . escapeshellarg($tmpFileOut) . ' '
    . escapeshellarg($tmpFileIn);

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
