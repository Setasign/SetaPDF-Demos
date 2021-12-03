<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/tektown/invoices/1012.pdf',
    $assetsDirectory . '/pdfs/tektown/invoices/1012-pdfa-3b.pdf',
    $assetsDirectory . '/pdfs/tektown/invoices/1157.pdf',
    $assetsDirectory . '/pdfs/tektown/invoices/1157-pdfa-3u.pdf',
];

$path = displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($path);
$metadata = $document->getInfo()->getMetadata();

$xpath = new DOMXPath($metadata);
$xpath->registerNamespace('x', 'adobe:ns:meta/');
$xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
$xpath->registerNamespace('pdfaid', 'http://www.aiim.org/pdfa/ns/id/');

$part = $xpath->query('//x:xmpmeta/rdf:RDF/rdf:Description/pdfaid:part')->item(0);
$conformance = $xpath->query('//x:xmpmeta/rdf:RDF/rdf:Description/pdfaid:conformance')->item(0);

if ($part === null || $conformance === null) {
    echo 'No PDF/A information found.';
    die();
}

echo sprintf('This file claims compliance with the PDF/A-%s%s standard.', $part->nodeValue, $conformance->nodeValue);
