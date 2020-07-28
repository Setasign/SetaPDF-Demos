<?php
if (!isset($_GET['action'])) {
    die();
}

// require SetaPDF
require_once __DIR__ . '/../../../../bootstrap.php';

date_default_timezone_set('Europe/Berlin');

$fileToSign = $assetDirectory . '/pdfs/tektown/Laboratory-Report.pdf';

// for demonstration purpose we use a session for state handling
// in a production environment you may use a more reasonable solution
session_start();

// a simple "controller":
switch ($_GET['action']) {
    case 'preview':
        $doc = file_get_contents($fileToSign);

        // Note: these lines are only required for the Verify.ink pdf viewer because of CORS
        header('Access-Control-Allow-Origin: https://verify.ink');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Expose-Headers: Content-Disposition');

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($fileToSign, '.pdf') . '.pdf"');
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
        if (isset($_SESSION['tmpDocument'])) {
            @unlink($_SESSION['tmpDocument']->getWriter()->getPath());
        }

        $data = json_decode(file_get_contents('php://input'));
        if (!isset($data->certificate)) {
            throw new Exception('Missing certificate!');
        }

        // load the PDF document
        $document = SetaPDF_Core_Document::loadByFilename($fileToSign);
        // create a signer instance
        $signer = new SetaPDF_Signer($document);
        // create a module instance
        $module = new SetaPDF_Signer_Signature_Module_Pades();

        // create a certificate instance
        $certificate = new SetaPDF_Signer_X509_Certificate($data->certificate);

        // pass the user certificate to the module
        $module->setCertificate($certificate);

        // setup information resolver manager
        $informationResolverManager = new SetaPDF_Signer_InformationResolver_Manager();
        $informationResolverManager->addResolver(new SetaPDF_Signer_InformationResolver_HttpCurlResolver());

        $extraCerts = new SetaPDF_Signer_X509_Collection();

        // get issuer certificates
        if (isset($data->useAIA) && $data->useAIA) {
            $certificates = [$certificate];
            while (count($certificates) > 0) {
                /** @var SetaPDF_Signer_X509_Certificate $currentCertificate */
                $currentCertificate = array_pop($certificates);
                /** @var SetaPDF_Signer_X509_Extension_AuthorityInformationAccess $aia */
                $aia = $currentCertificate->getExtensions()->get(SetaPDF_Signer_X509_Extension_AuthorityInformationAccess::OID);
                if ($aia instanceof SetaPDF_Signer_X509_Extension_AuthorityInformationAccess) {
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
            $signatureContentLength += (strlen($extraCert->get(SetaPDF_Signer_X509_Format::DER)) * 2);
        }

        $signer->setSignatureContentLength($signatureContentLength);

        unset($_SESSION['tsUrl']);
        // get timestamp information and use it
        if (isset($data->useTimestamp) && $data->useTimestamp) {
            /** @var SetaPDF_Signer_X509_Extension_TimeStamp $ts */
            $ts = $certificate->getExtensions()->get(SetaPDF_Signer_X509_Extension_TimeStamp::OID);
            if ($ts && $ts->getVersion() === 1 && $ts->requiresAuth() === false) {
                $_SESSION['tsUrl'] = $ts->getLocation();
                $signer->setSignatureContentLength($signatureContentLength + 6000);
            }
        }

        // you may use an own temporary file handler
        $tempPath = SetaPDF_Core_Writer_TempFile::createTempPath();

        // prepare the PDF
        $_SESSION['tmpDocument'] = $signer->preSign(
            new SetaPDF_Core_Writer_File($tempPath),
            $module
        );

        $_SESSION['module'] = $module;

        // prepare the response
        $response = [
            'dataToSign' => SetaPDF_Core_Type_HexString::str2hex(
                $module->getDataToSign($_SESSION['tmpDocument']->getHashFile())
            ),
            'extraCerts' => array_map(function (SetaPDF_Signer_X509_Certificate $cert) {
                return $cert->get(SetaPDF_Signer_X509_Format::PEM);
            }, $extraCerts->getAll()),
            'tsUrl' => isset($_SESSION['tsUrl']) ? $_SESSION['tsUrl'] : false
        ];

        // send it
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        break;

    // This action embeddeds the signature in the CMS container
    // and optionally requests and embeds the timestamp
    case 'complete':
        $data = json_decode(file_get_contents('php://input'));
        if (!isset($data->signature)) {
            die();
        }

        $data->signature = SetaPDF_Core_Type_HexString::hex2str($data->signature);

        // create the document instance
        $writer = new SetaPDF_Core_Writer_String();
        $document = SetaPDF_Core_Document::loadByFilename($fileToSign, $writer);
        $signer = new SetaPDF_Signer($document);

        // pass the signature to the signature modul
        $_SESSION['module']->setSignatureValue($data->signature);

        // get the CMS structur from the signature module
        $cms = (string) $_SESSION['module']->getCms();
        // add the timestamp (if available)
        if (isset($_SESSION['tsUrl'])) {
            $tsModule = new SetaPDF_Signer_Timestamp_Module_Rfc3161_Curl($_SESSION['tsUrl']);
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
        $key = 'id-' . (isset($_GET['id']) ? $_GET['id'] : '');
        if (!isset($_SESSION['pdfs']['docs'][$key])) {
            die();
        }

        $doc = $_SESSION['pdfs']['docs'][$key];

        // Note: these lines are only required for the Verify.ink pdf viewer because of CORS
        header('Access-Control-Allow-Origin: https://verify.ink');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Expose-Headers: Content-Disposition');

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
