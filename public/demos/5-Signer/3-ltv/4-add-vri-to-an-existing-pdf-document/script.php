<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a HTTP writer
$writer = new SetaPDF_Core_Writer_Http('Laboratory-Report-signed-LTV.pdf');
// let's get the document
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed-no-LTV.pdf', $writer
);

// define a trust-store
$trustedCerts = new SetaPDF_Signer_X509_Collection();
$trustedCerts->addFromFile($assetsDirectory . '/certificates/trusted/Intesi Group EU Qualified Electronic Signature CA G2.cer');

$fieldName = 'Signature1';

// create a VRI collector instance
$collector = new SetaPDF_Signer_ValidationRelatedInfo_Collector($trustedCerts);
// get VRI for the signature
try {
    $vri = $collector->getByFieldName($document, $fieldName);
} catch (SetaPDF_Signer_ValidationRelatedInfo_Exception $e) {
    echo 'Unable to create VRI data: ' . $e->getMessage();
    die();
}

// Use this snipped to trace the process of resolving VRI:
//foreach ($collector->getLogger()->getLogs() as $log) {
//    echo str_repeat('&nbsp;', $log->getDepth()*4);
//    echo $log->getMessage().'<br/>';
//}

// and add it to the document.
$dss = new SetaPDF_Signer_DocumentSecurityStore($document);
$dss->addValidationRelatedInfoByFieldName(
    $fieldName,
    $vri->getCrls(),
    $vri->getOcspResponses(),
    $vri->getCertificates()
);

// this is needed for Adobe Acrobat for some signatures (this one, too)
foreach ($vri->getOcspResponses() as $ocspResponse) {
    $key = $dss->getVriName($ocspResponse);
    $dss->addValidationRelatedInfo($key, [], [], [], new DateTime());
}

// save and finish the final document
$document->save()->finish();
