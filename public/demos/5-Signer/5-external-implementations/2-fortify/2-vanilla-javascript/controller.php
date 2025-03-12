<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Type\PdfHexString;
use setasign\SetaPDF2\Core\Writer\FileWriter;
use setasign\SetaPDF2\Core\Writer\StringWriter;
use setasign\SetaPDF2\Core\Writer\TempFileWriter;
use setasign\SetaPDF2\Signer\Cms\SignedData;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\InformationResolver\HttpCurlResolver;
use setasign\SetaPDF2\Signer\InformationResolver\Manager as InformationResolverManager;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\Timestamp\Module\Rfc3161\Curl as CurlTimestampModule;
use setasign\SetaPDF2\Signer\X509\Certificate;
use setasign\SetaPDF2\Signer\X509\Collection;
use setasign\SetaPDF2\Signer\X509\Extension\AuthorityInformationAccess;
use setasign\SetaPDF2\Signer\X509\Extension\TimeStamp as TimeStampExtension;
use setasign\SetaPDF2\Signer\X509\Format;

if (!isset($_GET['action'])) {
    die();
}

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$fileToSign = $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf';

// for demonstration purpose we use a session for state handling
// in a production environment you may use a more reasonable solution
session_start();

try {
    // a simple "controller":
    switch ($_GET['action']) {
        // This action expects the certificate of the signer.
        // It prepares the PDF document accordingly.
        case 'start':
            if (isset($_SESSION['tmpDocument'])) {
                @unlink($_SESSION['tmpDocument']->getWriter()->getPath());
            }

            $data = json_decode(file_get_contents('php://input'));
            if (!isset($data->certificate)) {
                throw new Exception('Missing certificate!');
            }

            // load the PDF document
            $document = Document::loadByFilename($fileToSign);
            // create a signer instance
            $signer = new Signer($document);
            // create a module instance
            $module = new PadesModule();
            $module->setDigest(Digest::SHA_256);

            // create a certificate instance
            $certificate = new Certificate($data->certificate);

            // pass the user certificate to the module
            $module->setCertificate($certificate);

            // setup information resolver manager
            $informationResolverManager = new InformationResolverManager();
            $informationResolverManager->addResolver(new HttpCurlResolver([
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_MAXREDIRS => 5
            ]));

            $extraCerts = new Collection();

            // get issuer certificates
            if (isset($data->useAIA) && $data->useAIA) {
                $certificates = [$certificate];
                while (count($certificates) > 0) {
                    /** @var Certificate $currentCertificate */
                    $currentCertificate = array_pop($certificates);
                    /** @var AuthorityInformationAccess $aia */
                    $aia = $currentCertificate->getExtensions()->get(AuthorityInformationAccess::OID);
                    if ($aia instanceof AuthorityInformationAccess) {
                        foreach ($aia->fetchIssuers($informationResolverManager)->getAll() as $issuer) {
                            $extraCerts->add($issuer);
                            $certificates[] = $issuer;
                        }
                    }
                }
            }

            $module->setExtraCertificates($extraCerts);

            $signatureContentLength = 10000;
            foreach ($extraCerts->getAll() as $extraCert) {
                $signatureContentLength += (strlen($extraCert->get(Format::DER)) * 2);
            }

            $signer->setSignatureContentLength($signatureContentLength);

            unset($_SESSION['tsUrl']);
            // get timestamp information and use it
            if (isset($data->useTimestamp) && $data->useTimestamp) {
                /** @var TimeStampExtension $ts */
                $ts = $certificate->getExtensions()->get(TimeStampExtension::OID);
                if ($ts && $ts->getVersion() === 1 && $ts->requiresAuth() === false) {
                    $_SESSION['tsUrl'] = $ts->getLocation();
                    $signer->setSignatureContentLength($signatureContentLength + 6000);
                }
            }

            // you may use an own temporary file handler
            $tempPath = TempFileWriter::createTempPath();

            // prepare the PDF
            $tmpDocument = $signer->preSign(
                new FileWriter($tempPath),
                $module
            );


            // prepare the response
            $response = [
                'dataToSign' => PdfHexString::str2hex(
                    $module->getDataToSign($tmpDocument->getHashFile())
                )
            ];

            $_SESSION['module'] = $module;
            $_SESSION['tmpDocument'] = $tmpDocument;

            // send it
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            break;

        // This action embeds the signature in the CMS container
        // and optionally requests and embeds the time stamp
        case 'complete':
            $data = json_decode(file_get_contents('php://input'));
            if (!isset($data->signature)) {
                die();
            }

            $data->signature = PdfHexString::hex2str($data->signature);

            // create the document instance
            $writer = new StringWriter();
            $document = Document::loadByFilename($fileToSign, $writer);
            $signer = new Signer($document);

            // pass the signature to the signature modul
            $_SESSION['module']->setSignatureValue($data->signature);

            // get the CMS structure from the signature module
            $cms = (string)$_SESSION['module']->getCms();

            // verify that the received signature matches to the CMS package and document.
            $signedData = new SignedData($cms);
            $signedData->setDetachedSignedData($_SESSION['tmpDocument']->getHashFile());
            if (!$signedData->verify($signedData->getSigningCertificate())) {
                throw new Exception('Signature cannot be verified!');
            }

            // add the timestamp (if available)
            if (isset($_SESSION['tsUrl'])) {
                $tsModule = new CurlTimestampModule($_SESSION['tsUrl']);
                $signer->setTimestampModule($tsModule);
                $cms = $signer->addTimeStamp($cms, $_SESSION['tmpDocument']);
            }

            // save the signature to the temporary document
            $signer->saveSignature($_SESSION['tmpDocument'], $cms);
            // clean up temporary file
            unlink($_SESSION['tmpDocument']->getWriter()->getPath());

            if (!isset($_SESSION['pdfs']['currentId'])) {
                $_SESSION['pdfs'] = ['currentId' => 0, 'docs' => []];
            } else {
                // reduce the session data to 5 signed files only
                while (count($_SESSION['pdfs']['docs']) > 5) {
                    array_shift($_SESSION['pdfs']['docs']);
                }
            }

            $id = $_SESSION['pdfs']['currentId']++;
            $_SESSION['pdfs']['docs']['id-' . $id] = $writer;
            // send the response
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['id' => $id]);
            break;

        // a download action
        case 'download':
            $key = 'id-' . ($_GET['id'] ?? '');
            if (!isset($_SESSION['pdfs']['docs'][$key])) {
                die();
            }

            $doc = $_SESSION['pdfs']['docs'][$key];

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($fileToSign, '.pdf') . '-signed.pdf"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . strlen($doc));
            echo $doc;
            flush();
            break;
    }
} catch (\Throwable $e) {
    header('Content-Type: application/json; charset=utf-8', true, 500);
    echo json_encode(['error' => $e->getMessage()]);
}