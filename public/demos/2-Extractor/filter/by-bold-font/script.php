<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\FilterInterface;
use setasign\SetaPDF2\Extractor\Strategy\WordStrategy;
use setasign\SetaPDF2\Extractor\TextItem;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document instance
$document = Document::loadByFilename($assetsDirectory . '/pdfs/Fuchslocher-Example.pdf');

// create an extractor instance
$extractor = new Extractor($document);

// create the word strategy...
$strategy = new WordStrategy();
// ...and pass it to the extractor
$extractor->setStrategy($strategy);

class BoldTextFilter implements FilterInterface
{
    /**
     * @param TextItem $textItem
     * @return bool
     */
    public function accept(TextItem $textItem)
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
