<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's use a session to hold the state of the workflow
session_start();

// first of all we need a path for a temporary file for the workflow
$tempDir = __DIR__ . '/tmp';
if (!is_writable($tempDir)) {
    throw new RuntimeException('The directory for the temporary file should be writeable.');
}

$state = isset($_GET['next']) && isset($_SESSION['workflow']['state']) ? $_SESSION['workflow']['state'] : null;

// IN A REAL SCENARIO YOU HAVE TO MAKE SURE THAT A WORKFLOW CAN ONLY BE EXECUTED ONCE
// SO YOU NEED TO LOCK THE WORKFLOW WHEN A STEP IS EXECUTED BY E.G. UPDATING THE STATE IN
// THE DATABASE OR QUEUE DURING EACH STEP AND NOT ONLY AT THE END OF THE SCRIPT.

if ($state === null) {
    $workflow = [
        'state' => null,
        'id' => 1234,
        'fileToSign' => $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
        // we need a path for a temporary file which needs to be hold during the whole process
        // in a real world szenario you should clean up the folder depending on the state of your queue items
        'tempPath' => $tempDir . '/task-id-1234.tmp',
        'signature' => null
    ];

    $document = SetaPDF_Core_Document::loadByFilename($workflow['fileToSign']);
    $signer = new SetaPDF_Signer($document);
    $signer->setSignatureContentLength(20000);

    // use an empty module instance to trigger implemented interface methods
    $module = new SetaPDF_Signer_Signature_Module_Pades();

    $workflow['tmpDocument'] = $signer->preSign(new SetaPDF_Core_Writer_File($workflow['tempPath']), $module);

    $workflow['state'] = 'prepared';

    echo 'Document prepared. Next step: <a href="?next=' . time() . '">Create Signature</a>';
}

if ($state === 'prepared') {
    $workflow = $_SESSION['workflow'];
    $document = SetaPDF_Core_Document::loadByFilename($workflow['fileToSign']);
    $signer = new SetaPDF_Signer($document);

    // now create a complete module instance
    $module = new SetaPDF_Signer_Signature_Module_Pades();
    $module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');
    $module->setPrivateKey('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem', '');

    // and create the signature
    $workflow['signature'] = $signer->createSignature($workflow['tmpDocument'], $module);

    //
    $dataToSign = $module->getDataToSign($workflow['tmpDocument']->getHashFile());
    $workflow['signature'] = createSignatureByAnotherImplementation($dataToSign);

    $workflow['state'] = 'signatureCreated';

    echo 'Signature created. Next step: ';
    echo '<a href="?next=' . time() . '&timestamp=1">Add Timestamp</a> or ';
    echo '<a href="?next=' . time() . '">Save Signature</a> | ';
    echo '<a href="?' . time() . '">Restart</a>';
}

if ($state === 'signatureCreated' && isset($_GET['timestamp'])) {
    $workflow = $_SESSION['workflow'];

    // catch this, if the link was clicked more than once
    if (isset($workflow['timestamped'])) {
        echo 'Signature already timestamped. Next step: ';
    } else {
        $document = SetaPDF_Core_Document::loadByFilename($workflow['fileToSign']);
        $signer = new SetaPDF_Signer($document);

        $url = 'https://freetsa.org/tsr';

        $tsModule = new SetaPDF_Signer_Timestamp_Module_Rfc3161_Curl($url);
        $tsModule->setDigest(SetaPDF_Signer_Digest::SHA_256);

        $signer->setTimestampModule($tsModule);

        $workflow['signature'] = $signer->addTimeStamp($workflow['signature'], $workflow['tmpDocument']);
        $workflow['timestamped'] = true;

        echo 'Timestamp added. Next step: ';
    }

    echo '<a href="?next=' . time() . '">Save Signature</a> | ';
    echo '<a href="?' . time() . '">Restart</a>';

} elseif ($state === 'signatureCreated') {
    $workflow = $_SESSION['workflow'];

    $writer   = new SetaPDF_Core_Writer_Http('async-signature.pdf');
    $document = SetaPDF_Core_Document::loadByFilename($workflow['fileToSign'], $writer);
    $signer   = new SetaPDF_Signer($document);

    $signer->saveSignature($workflow['tmpDocument'], $workflow['signature']);
}

$_SESSION['workflow'] = $workflow;
