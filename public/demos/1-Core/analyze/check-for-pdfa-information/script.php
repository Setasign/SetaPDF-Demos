<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Xmp\PdfA;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/tektown/invoices/1012.pdf',
    $assetsDirectory . '/pdfs/tektown/invoices/1012-pdfa-3b.pdf',
    $assetsDirectory . '/pdfs/tektown/invoices/1157.pdf',
    $assetsDirectory . '/pdfs/tektown/invoices/1157-pdfa-3u.pdf',
];

$path = displayFiles($files);

$document = Document::loadByFilename($path);

[$part, $conformance] = PdfA::getPartAndConformance($document);

if ($part === false || $conformance === false) {
    echo 'No PDF/A information found.';
    die();
}

echo sprintf('This file claims compliance with the PDF/A-%s%s standard.', $part, $conformance);
