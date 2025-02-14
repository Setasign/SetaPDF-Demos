<?php

use setasign\SetaPDF2\Core\DataStructure\Date;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Encoding;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfHexString;
use setasign\SetaPDF2\Signer\Cms\SignedData;
use setasign\SetaPDF2\Signer\Pem;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\Tsp\Token;
use setasign\SetaPDF2\Signer\ValidationRelatedInfo\IntegrityResult;
use setasign\SetaPDF2\Signer\X509\Certificate;
use setasign\SetaPDF2\Signer\X509\Chain;
use setasign\SetaPDF2\Signer\X509\Collection;
use setasign\SetaPDF2\Signer\X509\Format;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
];

$file = displayFiles($files, true, false, true);
if (is_array($file)) {
    extract($file);
} else {
    $filename = basename($file);
}

$trustedCerts = new Collection();
// PLEASE NOTICE THAT THESE FILE SHOULD BE UNDER YOUR CONTROL. IT'S UP TO YOU WHO YOU TRUST OR NOT!
$trustedCerts->add(Pem::extractFromFile(
    $assetsDirectory . '/certificates/trusted/cacert.pem'
));
$trustedCerts->add(Certificate::fromFile(
    $assetsDirectory . '/certificates/trusted/adoberoot.cer')
);
$trustedCerts->add(Certificate::fromFile(
    $assetsDirectory . '/certificates/trusted/entrust_2048_ca.cer')
);

// we store all found intermediate certificates in this collection
$extraCerts = new Collection();

/**
 * A helper method that verifies a certificate and dumps all information.
 *
 * @param Certificate $certificate
 * @param Collection $trustedCerts
 * @param Collection $extraCerts
 * @throws \setasign\SetaPDF2\Signer\Asn1\Exception
 * @throws \setasign\SetaPDF2\Signer\Exception
 */
function verifyAndDumpCertificate(
    Certificate $certificate,
    Collection $trustedCerts,
    Collection $extraCerts
) {
    echo 'Subject of signing certificate is:<br/>&nbsp;&nbsp;' .
        $certificate->getSubjectName() . '<br/>';
    echo 'Issuer:<br/>&nbsp;&nbsp;' . $certificate->getIssuerName() . '<br/>';

    $b64 = base64_encode($certificate->get(Format::DER));
    echo '<a href="data:application/x-pem-file;base64,' . $b64 . '" download="certificate.crt">download</a> | ' .
        '<a href="https://x509.io/?cert=' . urlencode($b64) . '" target="_blank">show details</a><br/>';
    echo 'Simple certificate dump:<br/>';
    echo '<div style="white-space: pre; height: 250px; overflow: auto;">' .
        print_r(\openssl_x509_parse($certificate->get()), true) . '</div>';

    $chain = new Chain($trustedCerts);
    $chain->getExtraCertificates()->add($extraCerts);
    $path = $chain->buildPath($certificate);
    if ($path === false) {
        if ($certificate->getSubjectName() === $certificate->getIssuerName()) {
            echo '<span style="color:darkgray">Certificate is self-signed. ';
            if ($certificate->verify()) {
                echo 'And was verified successful.';
            }
            echo '</span><br/>';
            if ($trustedCerts->contains($certificate)) {
                echo '<span style="color:green">It is located in your trusted certificates store.</span><br/>';
            } else {
                echo '<span style="color:red">It is not located in your trusted certificates store.</span><br/>';
            }
        } else {
            echo '<span style="color:darkgray">Signer\'s identity is unknown because it has not been ' .
                'included in your list of trusted certificates and none of its parent certificate are ' .
                'trusted certificates.</span><br/>';
        }

    } else {
        echo '<span style="color:green">Certificate and its path were validated successfully.</span><br/>';

        echo 'Path is:<br/>';
        foreach (array_reverse($path) as $no => $certificateInPath) {
            echo str_repeat('&nbsp;', ($no + 1) * 4);
            echo $certificateInPath->getSubjectName() . '<br/>';
        }
    }

    if (!$certificate->isValidAt(new DateTime())) {
        echo '<span style="color:darkgray">Certificate is expired and was valid from ' .
            $certificate->getValidFrom()->format('Y-m-d H:i:s') . ' to ' .
            $certificate->getValidTo()->format('Y-m-d H:i:s') . '</span><br/>';
    }
}

echo '<h1>Checking signatures in ' . htmlspecialchars($filename) . '</h1>';

try {
    $document = Document::loadByFilename($file);
    $signatureFieldNames = Signer::getSignatureFieldNames($document);

    foreach ($signatureFieldNames AS $fieldName) {
        try {
            echo '<h2>Validating signature in signature field: ' . $fieldName . '</h2>';

            $integrityResult = IntegrityResult::create($document, $fieldName);

            if ($integrityResult->getStatus() === IntegrityResult::STATUS_NOT_SIGNED) {
                echo '<span style="color:darkgray">Field is not signed.</span><br/>';
                continue;
            }

            $signedData = $integrityResult->getSignedData();
            $extraCerts->add($signedData->getCertificates());
            if ($signedData instanceof Token) {
                echo "Signature is a document level timestamp.<br/>";
            }

            $signatureData = (string)$signedData->getAsn1();

            echo '<a href="https://lapo.it/asn1js/#' . PdfHexString::str2hex($signatureData) . '" ' .
                'target="_blank">asn1js</a> | ';
            echo '<a href="data:application/pkcs7-mime;base64,' . base64_encode($signatureData) . '" ' .
                'download="signature.pkcs7">download</a><br />';

            if ($integrityResult->getStatus() === IntegrityResult::STATUS_VALID) {
                if ($integrityResult->isSignedRevision()) {
                    echo '<span style="color:green">The signature is valid for the signed revision of this document. ' .
                        'There were changes in later revisions.</span><br />';
                } else {
                    echo '<span style="color:green">Document has not been modified since this signature was applied.' .
                        '</span><br />';
                }
            } else {
                echo '<span style="color:red;">Document has been altered or corrupted since it was signed.</span><br/>';
            }

            $signingCertificate = $integrityResult->getSignedData()->getSigningCertificate();
            verifyAndDumpCertificate($signingCertificate, $trustedCerts, $extraCerts);

            // check for timestamp attribute
            if ($signedData instanceof SignedData) {
                $timestampAttribute = $signedData->getUnsignedAttribute('1.2.840.113549.1.9.16.2.14');
                if ($timestampAttribute) {
                    echo '<br/>';
                    echo 'The signature includes an embedded timestamp:<div style="margin-left: 20px;">';
                    $tspToken = new Token($timestampAttribute->getChild(0));
                    $tspCertificate = $tspToken->getSigningCertificate($extraCerts);
                    if ($tspCertificate === false) {
                        echo '<span style="color:red">Signing certificate of the timestamp could not be found!</span><br/>';
                    } else {
                        if ($tspToken->verify($tspCertificate)) {
                            echo '<span style="color:green">The timestamp verification was succesfully!</span><br/>';
                        } else {
                            echo '<span style="color:red">The timestamp verification was NO succesfully!</span><br/>';
                        }
                    }

                    // Check if timestamp belongs to the signature it is part of
                    if ($tspToken->verifyMessageImprint($signedData->getSignatureValue(false))) {
                        echo '<span style="color:green">Message imprint of the timestamp matches.</span><br/>';
                    } else {
                        echo '<span style="color:red;">Timestamp has a different message imprint than the outer CMS container.</span>';
                    }

                    verifyAndDumpCertificate($tspCertificate, $trustedCerts, $extraCerts);
                    echo '</div>';
                }
            }

            echo '<br/>';
            echo 'Signature properties:<br/>';

            /** @var PdfDictionary $dictionary */
            $dictionary = $integrityResult->getField()->getValue();
            // get PDF signature properties
            foreach ([
                         Signer::PROP_NAME,
                         Signer::PROP_LOCATION,
                         Signer::PROP_CONTACT_INFO,
                         Signer::PROP_REASON,
                         Signer::PROP_TIME_OF_SIGNING
                     ] AS $property) {
                if (!$dictionary->offsetExists($property)) {
                    continue;
                }

                echo $property . ': ';
                $value = $dictionary->getValue($property)->ensure()->getValue();
                if ($property == Signer::PROP_TIME_OF_SIGNING) {
                    $value = Date::stringToDateTime($value);
                    $value = $value->format('Y-m-d H:i:s');
                } else {
                    $value = Encoding::convertPdfString($value);
                }
                echo $value . '<br />';
            }

            // check for certification status:
            $references = $dictionary->getValue('Reference');
            if ($references) {
                echo '<span style="color:#22caff;">Document is certified by this signature.</span><br />';
                // Check references for allowed changes
            }

        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        echo '<hr>';
    }

    if (count($signatureFieldNames) === 0) {
        echo 'No signature fields found.';
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
