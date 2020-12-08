<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/Fuchslocher-Example.pdf');

// create an extractor instance
$extractor = new SetaPDF_Extractor($document);

// create the word strategy...
$strategy = new SetaPDF_Extractor_Strategy_Word();
// ...and pass it to the extractor
$extractor->setStrategy($strategy);

class BoldTextFilter implements SetaPDF_Extractor_Filter_FilterInterface
{
    /**
     * @param SetaPDF_Extractor_TextItem $textItem
     * @return bool|string
     */
    public function accept(SetaPDF_Extractor_TextItem $textItem)
    {
        $font = $textItem->getFont();
        return $font->isBold() || stripos($font->getFontName(), 'bold') !== false;
    }
}

// creat an instance of the filter
$filter = new BoldTextFilter();
// ...pass it to the strategy
$strategy->setFilter($filter);

// get access to the document pages
$pages = $document->getCatalog()->getPages();

// iterate over the pages and extract the words:
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {

    $words = $extractor->getResultByPageNumber($pageNo);
    echo '<b>' . count($words) . ' bold word(s) found on Page #' . $pageNo . ':</b><ul>';

    foreach ($words as $word) {
        echo '<li>' . htmlspecialchars($word->getString()) . '</li>';
    }

    echo '</ul>';
}
