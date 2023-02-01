<?php

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

$trustedCerts = new SetaPDF_Signer_X509_Collection();
// PLEASE NOTICE THAT THESE FILE SHOULD BE UNDER YOUR CONTROL. IT'S UP TO YOU WHO YOU TRUST OR NOT!
$trustedCerts->add(SetaPDF_Signer_Pem::extractFromFile(
    $assetsDirectory . '/certificates/trusted/cacert.pem'
));
$trustedCerts->add(SetaPDF_Signer_X509_Certificate::fromFile(
    $assetsDirectory . '/certificates/trusted/adoberoot.cer')
);
$trustedCerts->add(SetaPDF_Signer_X509_Certificate::fromFile(
    $assetsDirectory . '/certificates/trusted/entrust_2048_ca.cer')
);

// we store all found intermediate certificates in this collection
$extraCerts = new SetaPDF_Signer_X509_Collection();

/**
 * A helper method that verifies a certificate and dumps all information.
 *
 * @param SetaPDF_Signer_X509_Certificate $certificate
 * @param SetaPDF_Signer_X509_Collection $trustedCerts
 * @param SetaPDF_Signer_X509_Collection $extraCerts
 * @throws SetaPDF_Signer_Asn1_Exception
 * @throws SetaPDF_Signer_Exception
 */
function verifyAndDumpCertificate(
    SetaPDF_Signer_X509_Certificate $certificate,
    SetaPDF_Signer_X509_Collection $trustedCerts,
    SetaPDF_Signer_X509_Collection $extraCerts
)
{
    echo 'Subject of signing certificate is:<br/>&nbsp;&nbsp;' .
        $certificate->getSubjectName() . '<br/>';
    echo 'Issuer:<br/>&nbsp;&nbsp;' . $certificate->getIssuerName() . '<br/>';

    $b64 = base64_encode($certificate->get(SetaPDF_Signer_X509_Format::DER));
    echo '<a href="data:application/x-pem-file;base64,' . $b64 . '" download="certificate.crt">download</a> | ' .
        '<a href="https://understandingwebpki.com/?cert=' . urlencode($b64) . '" target="_blank">show details</a><br/>';
    echo 'Simple certificate dump:<br/>';
    echo '<div style="white-space: pre; height: 250px; overflow: auto;">' .
        print_r(openssl_x509_parse($certificate->get()), true) . '</div>';

    $chain = new SetaPDF_Signer_X509_Chain($trustedCerts);
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
    $document = SetaPDF_Core_Document::loadByFilename($file);
    $signatureFieldNames = SetaPDF_Signer_ValidationRelatedInfo_Collector::getSignatureFieldNames($document);

    foreach ($signatureFieldNames AS $fieldName) {
        try {
            echo '<h2>Validating signature in signature field: ' . $fieldName . '</h2>';

            $integrityResult = SetaPDF_Signer_ValidationRelatedInfo_IntegrityResult::create($document, $fieldName);

            if ($integrityResult->getStatus() === SetaPDF_Signer_ValidationRelatedInfo_IntegrityResult::STATUS_NOT_SIGNED) {
                echo '<span style="color:darkgray">Field is not signed.</span><br/>';
                continue;
            }

            $signedData = $integrityResult->getSignedData();
            $extraCerts->add($signedData->getCertificates());
            if ($signedData instanceof SetaPDF_Signer_Tsp_Token) {
                echo "Signature is a document level timestamp.<br/>";
            }

            $signatureData = (string)$signedData->getAsn1();

            echo '<a href="https://lapo.it/asn1js/#' . SetaPDF_Core_Type_HexString::str2hex($signatureData) . '" ' .
                'target="_blank">asn1js</a> | ';
            echo '<a href="data:application/pkcs7-mime;base64,' . base64_encode($signatureData) . '" ' .
                'download="signature.pkcs7">download</a><br />';

            if ($integrityResult->getStatus() === SetaPDF_Signer_ValidationRelatedInfo_IntegrityResult::STATUS_VALID) {
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
            if ($signedData instanceof SetaPDF_Signer_Cms_SignedData) {
                $timestampAttribute = $signedData->getUnsignedAttribute('1.2.840.113549.1.9.16.2.14');
                if ($timestampAttribute) {
                    echo '<br/>';
                    echo 'The signature includes an embedded timestamp:<div style="margin-left: 20px;">';
                    $tspToken = new SetaPDF_Signer_Tsp_Token($timestampAttribute->getChild(0));
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

            /** @var SetaPDF_Core_Type_Dictionary $dictionary */
            $dictionary = $integrityResult->getField()->getValue();
            // get PDF signature properties
            foreach ([
                         SetaPDF_Signer::PROP_NAME,
                         SetaPDF_Signer::PROP_LOCATION,
                         SetaPDF_Signer::PROP_CONTACT_INFO,
                         SetaPDF_Signer::PROP_REASON,
                         SetaPDF_Signer::PROP_TIME_OF_SIGNING
                     ] AS $property) {
                if (!$dictionary->offsetExists($property)) {
                    continue;
                }

                echo $property . ': ';
                $value = $dictionary->getValue($property)->ensure()->getValue();
                if ($property == SetaPDF_Signer::PROP_TIME_OF_SIGNING) {
                    $value = SetaPDF_Core_DataStructure_Date::stringToDateTime($value);
                    $value = $value->format('Y-m-d H:i:s');
                } else {
                    $value = SetaPDF_Core_Encoding::convertPdfString($value);
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
