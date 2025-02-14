<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Type\PdfHexString;
use setasign\SetaPDF2\Core\Writer\StringWriter;
use setasign\SetaPDF2\Core\Writer\TempFileWriter;
use setasign\SetaPDF2\Signer\Cms\SignedData;
use setasign\SetaPDF2\Signer\InformationResolver\HttpCurlResolver;
use setasign\SetaPDF2\Signer\InformationResolver\Manager as InformationResolverManager;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\Timestamp\Module\Rfc3161\Curl as CurlTimestampModule;
use setasign\SetaPDF2\Signer\X509\Certificate;
use setasign\SetaPDF2\Signer\X509\Collection;
use setasign\SetaPDF2\Signer\X509\Extension\AuthorityInformationAccess;
use setasign\SetaPDF2\Signer\X509\Extension\TimeStamp;
use setasign\SetaPDF2\Signer\X509\Format;

if (!isset($_GET['action'])) {
    die();
}

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$filesToSign = [
    'tektown' => $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    'camtown' => $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    'lenstown' => $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
];

// for demonstration purpose we use a session for state handling
// in a production environment you may use a more reasonable solution
session_start();

try {
    // a simple "controller":
    switch ($_GET['action']) {
        case 'preview':
            if (!array_key_exists($_GET['file'], $filesToSign)) {
                http_response_code(404);
                die();
            }

            $doc = file_get_contents($filesToSign[$_GET['file']]);

            // Note: these lines are only required for the Verify.ink pdf viewer because of CORS
            header('Access-Control-Allow-Origin: https://verify.ink');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Expose-Headers: Content-Disposition');

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $_GET['file'] . '.pdf"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . strlen($doc));
            echo $doc;
            flush();
            break;

        // This action expects the certificate of the signer.
        // It prepares the PDF document accordingly.
        case 'start':
            if (isset($_SESSION['tmpDocuments'])) {
                foreach ($_SESSION['tmpDocuments'] as $tmpDocument) {
                    @unlink($tmpDocument['tmpDocument']->getWriter()->getPath());
                }
            }

            $data = json_decode(file_get_contents('php://input'));
            if (!isset($data->certificate)) {
                throw new Exception('Missing certificate!');
            }

            // create a certificate instance
            $certificate = new Certificate($data->certificate);
            $extraCerts = new Collection();

            // setup information resolver manager
            $informationResolverManager = new InformationResolverManager();
            $informationResolverManager->addResolver(new HttpCurlResolver([
                \CURLOPT_FOLLOWLOCATION => true,
                \CURLOPT_MAXREDIRS => 5
            ]));

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

            $signatureContentLength = 10000;
            foreach ($extraCerts->getAll() as $extraCert) {
                $signatureContentLength += (strlen($extraCert->get(Format::DER)) * 2);
            }


            unset($_SESSION['tsUrl']);
            // get timestamp information and use it
            if (isset($data->useTimestamp) && $data->useTimestamp) {
                /** @var TimeStamp $ts */
                $ts = $certificate->getExtensions()->get(TimeStamp::OID);
                if ($ts && $ts->getVersion() === 1 && $ts->requiresAuth() === false) {
                    $_SESSION['tsUrl'] = $ts->getLocation();
                    $signatureContentLength += 6000;
                }
            }

            $tmpDocuments = [];
            foreach ($filesToSign as $k => $fileToSign) {
                // load the PDF document
                $document = Document::loadByFilename($fileToSign);
                // create a signer instance
                $signer = new Signer($document);
                // create a module instance
                $module = new PadesModule();


                // pass the user certificate to the module
                $module->setCertificate(clone $certificate);
                $module->setExtraCertificates(clone $extraCerts);
                $signer->setSignatureContentLength($signatureContentLength);

                // A simple example to add a visible signature.
//                $field = $signer->addSignatureField(
//                    'Signature', 1, \setasign\SetaPDF2\Signer\SignatureField::POSITION_LEFT_TOP, ['x' => 20, 'y' => -20], 180, 60
//                );
//                $signer->setSignatureFieldName($field->getQualifiedName());
//
//                $appearance = new \setasign\SetaPDF2\Signer\Signature\Appearance\Dynamic($module);
//                $signer->setAppearance($appearance);

                // you may use an own temporary file handler
                $tempPath = TempFileWriter::createTempPath();

                $tmpDocuments[$k] = [
                    'tmpDocument' => $signer->preSign(
                        new \setasign\SetaPDF2\Core\Writer\FileWriter($tempPath),
                        $module
                    ),
                    'module' => $module
                ];
            }

            // prepare the response
            $response = [
                'dataToSign' => array_map(function ($tmpDocument) {
                    return PdfHexString::str2hex(
                        $tmpDocument['module']->getDataToSign($tmpDocument['tmpDocument']->getHashFile())
                    );
                }, $tmpDocuments),
                'extraCerts' => array_map(function (Certificate $cert) {
                    return $cert->get(Format::PEM);
                }, $extraCerts->getAll()),
                'tsUrl' => $_SESSION['tsUrl'] ?? false
            ];

            $_SESSION['tmpDocuments'] = $tmpDocuments;

            // send it
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($response);
            break;

        // This action embeds the signature in the CMS container
        // and optionally requests and embeds the timestamp
        case 'complete':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['signatures'])) {
                die();
            }

            $data['signatures'] = array_map([PdfHexString::class, 'hex2str'], $data['signatures']);

            $resultIds = [];
            foreach ($filesToSign as $key => $fileToSign) {
                // create the document instance
                $writer = new StringWriter();
                $document = Document::loadByFilename($fileToSign, $writer);
                $signer = new Signer($document);

                // pass the signature to the signature modul
                $_SESSION['tmpDocuments'][$key]['module']->setSignatureValue($data['signatures'][$key]);

                // get the CMS structure from the signature module
                $cms = (string)$_SESSION['tmpDocuments'][$key]['module']->getCms();

                // verify that the received signature matches to the CMS package and document.
                $signedData = new SignedData($cms);
                $signedData->setDetachedSignedData($_SESSION['tmpDocuments'][$key]['tmpDocument']->getHashFile());
                if (!$signedData->verify($signedData->getSigningCertificate())) {
                    throw new Exception('Signature cannot be verified!');
                }

                // add the timestamp (if available)
                if (isset($_SESSION['tsUrl'])) {
                    $tsModule = new CurlTimestampModule($_SESSION['tsUrl']);
                    $signer->setTimestampModule($tsModule);
                    $cms = $signer->addTimeStamp($cms, $_SESSION['tmpDocuments'][$key]['tmpDocument']);
                }

                // save the signature to the temporary document
                $signer->saveSignature($_SESSION['tmpDocuments'][$key]['tmpDocument'], $cms);
                // clean up temporary file
                unlink($_SESSION['tmpDocuments'][$key]['tmpDocument']->getWriter()->getPath());

                if (!isset($_SESSION['pdfs']['currentId'])) {
                    $_SESSION['pdfs'] = ['currentId' => 0, 'docs' => []];
                } else {
                    // reduce the session data to 6 signed files only
                    while (count($_SESSION['pdfs']['docs']) > 6) {
                        array_shift($_SESSION['pdfs']['docs']);
                    }
                }

                $id = $_SESSION['pdfs']['currentId']++;
                $_SESSION['pdfs']['docs']['id-' . $id] = $writer;
                $resultIds[$key] = $id;
            }

            // send the response
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ids' => $resultIds
            ]);
            break;

        // a download action
        case 'download':
            $key = 'id-' . ($_GET['id'] ?? '');
            if (!isset($_SESSION['pdfs']['docs'][$key])) {
                die();
            }

            $doc = $_SESSION['pdfs']['docs'][$key];

            // Note: these lines are only required for the Verify.ink pdf viewer because of CORS
            header('Access-Control-Allow-Origin: https://verify.ink');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Expose-Headers: Content-Disposition');

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' .  ($_GET['name'] ?? 'document') . '-signed.pdf"');
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