<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\DocumentSecurityStore;
use setasign\SetaPDF2\Signer\ValidationRelatedInfo\Collector;
use setasign\SetaPDF2\Signer\ValidationRelatedInfo\Exception as ValidationRelatedInfoException;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create an HTTP writer
$writer = new HttpWriter('Laboratory-Report-signed-LTV.pdf');
// let's get the document
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed-no-LTV.pdf', $writer
);

// define a trust-store
$trustedCerts = new \setasign\SetaPDF2\Signer\X509\Collection();
$trustedCerts->addFromFile($assetsDirectory . '/certificates/trusted/Intesi Group EU Qualified Electronic Signature CA G2.cer');

$fieldName = 'Signature1';

// create a VRI collector instance
$collector = new Collector($trustedCerts);
// get VRI for the signature
try {
    $vri = $collector->getByFieldName($document, $fieldName);
} catch (ValidationRelatedInfoException $e) {
    echo 'Unable to create VRI data: ' . $e->getMessage();
    die();
}

// Use this snipped to trace the process of resolving VRI:
//foreach ($collector->getLogger()->getLogs() as $log) {
//    echo str_repeat('&nbsp;', $log->getDepth()*4);
//    echo $log->getMessage().'<br/>';
//}

// and add it to the document.
$dss = new DocumentSecurityStore($document);
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
