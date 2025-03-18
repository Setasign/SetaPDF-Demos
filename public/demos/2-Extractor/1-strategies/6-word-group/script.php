<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Word;
use setasign\SetaPDF2\Extractor\Strategy\WordGroupStrategy;

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

$strategy = new WordGroupStrategy();
$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $wordGroups = $extractor->getResultByPageNumber($pageNo);

    echo '<b>There are ' . count($wordGroups) . ' word groups on Page #' . $pageNo . ':</b><br/>';

    foreach ($wordGroups as $i => $words) {
        echo '<p>';

        /** @var Word $word */
        foreach ($words as $word) {
            echo $word->getString() . ' ';
        }

        echo '</p><hr/>';
    }
}
