<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$data = [
    '24' => '24pt',
    '18' => '18pt',
    '12' => '12pt'
];

$fontSize = displaySelect('Filter by:', $data);

// create a document instance
$document = \SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/Brand-Guide.pdf');

// create an extractor instance
$extractor = new \SetaPDF_Extractor($document);

// create the word strategy...
$strategy = new \SetaPDF_Extractor_Strategy_Word();
// ...and pass it to the extractor
$extractor->setStrategy($strategy);

// creat an instance of the font size filter
$filter = new \SetaPDF_Extractor_Filter_FontSize((float)$fontSize);
// ...pass it to the strategy
$strategy->setFilter($filter);

// get access to the document pages
$pages = $document->getCatalog()->getPages();

// iterate over the pages and extract the words:
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {

    $words = $extractor->getResultByPageNumber($pageNo);
    echo '<b>' . count($words) . ' word(s) found on Page #' . $pageNo
        . ' with the font size ' . $fontSize . 'pt:</b><ul>';

    foreach ($words as $word) {
        echo '<li>' . htmlspecialchars($word->getString()) . '</li>';
    }

    echo '</ul>';
}
