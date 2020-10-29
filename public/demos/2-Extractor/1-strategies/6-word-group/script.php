<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/lenstown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);
$extractor = new SetaPDF_Extractor($document);

$strategy = new SetaPDF_Extractor_Strategy_WordGroup();
$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $wordGroups = $extractor->getResultByPageNumber($pageNo);

    echo '<b>There are ' . count($wordGroups) . ' word groups on Page #' . $pageNo . ':</b><br/>';

    foreach ($wordGroups as $i => $words) {
        echo '<p>';

        /** @var SetaPDF_Extractor_Result_Word $word */
        foreach ($words as $word) {
            echo $word->getString() . ' ';
        }

        echo '</p><hr/>';
    }
}
