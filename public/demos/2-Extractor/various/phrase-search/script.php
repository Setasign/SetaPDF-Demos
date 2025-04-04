<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Strategy\WordGroupStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

session_start();

$text = displayText('Search for:', ($_GET['data'] ?? ''), true, 'script.php?data=');
$regex = '/' . implode('\s', array_map(static function($part) {
    return preg_quote($part, '/');
}, preg_split('/\s/', trim($text)))) . '/ui';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    new HttpWriter('search.pdf', true)
);

if ($regex !== '//ui') {
    // initiate an extractor instance
    $extractor = new Extractor($document);

    // define the word group strategy
    $strategy = new WordGroupStrategy();
    $extractor->setStrategy($strategy);

    $pages = $document->getCatalog()->getPages();

    for ($pageNo = 1, $pageCount = $pages->count(); $pageNo < $pageCount; $pageNo++) {
        $currentPage = $document->getCatalog()->getPages()->getPage($pageNo);

        // simulate caching
        if (!isset($_SESSION[__FILE__]['words'][$pageNo])) {
            $wordGroups = $extractor->getResultByPageNumber($pageNo);
            $_SESSION[__FILE__]['wordGroups'][$pageNo] = $wordGroups;
        } else {
            $wordGroups = $_SESSION[__FILE__]['wordGroups'][$pageNo];
        }

        foreach ($wordGroups as $wordGroup) {
            $result = $wordGroup->search($regex);

            if (count($result) > 0) {
                // ensure a clean transformation matrix
                $currentPage->getContents()->encapsulateExistingContentInGraphicState();
                // get canvas object for the current page
                $canvas = $currentPage->getCanvas();
                // get access to the path instance
                $path = $canvas->path();
                $path->setLineWidth(2);

                // iterate over all phrases
                foreach ($result as $i => $words) {
                    foreach ($words->getItems() as $word) {
                        $bounds = $word->getBounds();
                        foreach ($bounds as $bound) {
                            // draw the bounds through the pages canvas object.
                            $canvas->setStrokingColor([1, 0, 0]);
                            $path->moveTo($bound->getUr()->getX(), $bound->getUr()->getY())
                                ->lineTo($bound->getUl()->getX(), $bound->getUl()->getY())
                                ->lineTo($bound->getLl()->getX(), $bound->getLl()->getY())
                                ->lineTo($bound->getLr()->getX(), $bound->getLr()->getY())
                                ->closeAndStroke();
                        }
                    }
                }
            }
        }
    }
}

// save the resulting document
$document->save()->finish();
