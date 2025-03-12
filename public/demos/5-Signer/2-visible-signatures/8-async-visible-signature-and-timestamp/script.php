<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\FileWriter;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\Signature\Appearance\Dynamic;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\SignatureField;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\Timestamp\Module\Rfc3161\Curl as CurlTimestampModule;

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

    // add a visible signature field
    $field = $signer->addSignatureField(
        SignatureField::DEFAULT_FIELD_NAME,
        1,
        SignatureField::POSITION_RIGHT_TOP,
        ['x' => -160, 'y' => -100],
        180,
        60
    );

    // and define that you want to use this field
    $signer->setSignatureFieldName($field->getQualifiedName());

    // use an empty module instance to trigger implemented interface methods
    $module = new PadesModule();
    // we already need to pass the certificate in this state because of the dynamic appearance
    $module->setCertificate('file://' . $assetsDirectory . '/certificates/setapdf-no-pw.pem');

    // create an appearance instance
    $appearance = new Dynamic($module);
    // disable this, because the time would differ from the final one because it is done async
    $appearance->setShow(Dynamic::CONFIG_DATE, false);
    // pass it to the signer instance
    $signer->setAppearance($appearance);

    $workflow['tmpDocument'] = $signer->preSign(new FileWriter($workflow['tempPath']), $module);

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

    // and create the signature
    $workflow['signature'] = $signer->createSignature($workflow['tmpDocument'], $module);

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
