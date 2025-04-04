<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Extractor\Extractor;

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

// The plain text strategy is the default strategy, so we don't need to do this:
//$strategy = new \setasign\SetaPDF2\Extractor\Strategy\Plain();
//$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $result = $extractor->getResultByPageNumber($pageNo);
    echo '<b>Result for Page #' . $pageNo . ':</b><br/>';
    echo '<pre>' . htmlspecialchars($result) . '</pre>';
    echo '<br/><br/>';
}
