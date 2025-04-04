<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\FileWriter;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Timestamp\Module\Rfc3161\Curl as CurlTimestampModule;
use setasign\SetaPDF2\Signer\Signer;

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

    $document = Document::loadByFilename($workflow['fileToSign']);
    $signer = new Signer($document);
    $signer->setSignatureContentLength(20000);

    // use an empty module instance to trigger implemented interface methods
    $module = new PadesModule();

    $tmpDocument = $signer->preSign(new FileWriter($workflow['tempPath']), $module);
    $workflow['tmpDocument'] = $tmpDocument;

    // if you don't want to save the TmpDocument instance in your workflow, you will need at least the ByteRange
    // value to be able to recreate the TmpDocument instance later:
//    $workflow['tmpDocumentByteRange'] = $tmpDocument->getByteRange();

    $workflow['state'] = 'prepared';

    echo 'Document prepared. Next step: <a href="?next=' . time() . '">Create Signature</a>';
}

if ($state === 'prepared') {
    $workflow = $_SESSION['workflow'];
    $document = Document::loadByFilename($workflow['fileToSign']);
    $signer = new Signer($document);

    // now create a complete module instance
    $module = new PadesModule();
    $module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');
    $module->setPrivateKey('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem', '');

    $tmpDocument = $workflow['tmpDocument'];

    // If you want to create a TmpDocument instance manually, you need to do this in each step:
//    $tmpDocument = new \setasign\SetaPDF2\Signer\TmpDocument(new \setasign\SetaPDF2\Core\Writer\FileWriter($workflow['tempPath']));
//    $tmpDocument->setDocumentIdentification($document);
//    $tmpDocument->updateIdentificationHash();
//    $tmpDocument->setByteRange($workflow['tmpDocumentByteRange']);

    // and create the signature
    $workflow['signature'] = $signer->createSignature($tmpDocument, $module);

    // if the signature should be created by another service or application you don't need a module instance
    // in this step but you have to build the digest on your own. Please notice that the external service
    // may need the digest encoded in a specific format (e.g. hex- or base64 encoded). The following lines create
    // and pass a binary version of the digest.
//    $digestMethod = 'sha256';
//    $digest = hash_file($digestMethod, $workflow['tmpDocument']->getHashFile(), true);
//    $workflow['signature'] = createSignatureByAnotherImplementation($digest, $digestMethod);

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
        $document = Document::loadByFilename($workflow['fileToSign']);
        $signer = new Signer($document);

        $url = 'https://freetsa.org/tsr';

        $tsModule = new CurlTimestampModule($url);
        $tsModule->setDigest(Digest::SHA_256);

        $signer->setTimestampModule($tsModule);

        $workflow['signature'] = $signer->addTimeStamp($workflow['signature'], $workflow['tmpDocument']);
        $workflow['timestamped'] = true;

        echo 'Timestamp added. Next step: ';
    }

    echo '<a href="?next=' . time() . '">Save Signature</a> | ';
    echo '<a href="?' . time() . '">Restart</a>';

} elseif ($state === 'signatureCreated') {
    $workflow = $_SESSION['workflow'];

    $writer   = new HttpWriter('async-signature.pdf');
    $document = Document::loadByFilename($workflow['fileToSign'], $writer);
    $signer   = new Signer($document);

    $signer->saveSignature($workflow['tmpDocument'], $workflow['signature']);
}

$_SESSION['workflow'] = $workflow;
