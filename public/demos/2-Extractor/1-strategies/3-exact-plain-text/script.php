<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Strategy\ExactPlainStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/lenstown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

$path = displayFiles($files);

$document = Document::loadByFilename($path);
$extractor = new Extractor($document);

$strategy = new ExactPlainStrategy();
$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $result = $extractor->getResultByPageNumber($pageNo);
    echo '<b>Result for Page #' . $pageNo . ':</b><br/>';
    echo '<pre>' . htmlspecialchars($result) . '</pre>';
    echo '<br/><br/>';
}
