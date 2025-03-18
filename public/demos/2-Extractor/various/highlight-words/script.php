<?php

use setasign\SetaPDF2\Core\DataStructure\Rectangle as RectangleDataStructure;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\HighlightAnnotation;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Strategy\WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$word = displaySelect('Highlight:', [
    'Terms' => 'Terms',
    'tektown' => 'tektown',
    'Information' =>  'Information',
    'con' => 'con',
    'eos' => 'eos',
    'sed' => 'sed',
    'volum' => 'volum'
]);

// load the document
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Terms-and-Conditions.pdf',
    new HttpWriter('document.pdf', true)
);

// initate an extractor instance
$extractor = new Extractor($document);

// create the word extraction strategy and pass it to the extractor instance
$strategy = new WordStrategy();
$extractor->setStrategy($strategy);

// get access to the documents pages instance
$pages = $document->getCatalog()->getPages();

// check if the words are saved in the temporary cache
if (isset($_SESSION['wordsPerPage'])) {
    $wordsPerPage = $_SESSION['wordsPerPage'];
    // otherwise...
} else {
    $wordsPerPage = $_SESSION['wordsPerPage'] = [];

    // walk through the pages and extract the word
    for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
        $words = $extractor->getResultByPageNumber($pageNo);
        // restructure the data to be less memory intensive in the "cache"
        foreach ($words AS $_word) {
            $wordsPerPage[$pageNo][] = [
                'string' => $_word->getString(),
                'bounds' => $_word->getBounds()
            ];
        }
    }

    // cache the words per page
    $_SESSION['wordsPerPage'] = $wordsPerPage;
    unset($words);
}

// a simple counter
$found = 0;

// walk through the pages...
for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    // get access to the pages annotations instance
    $annotations = $pages->getPage($pageNo)->getAnnotations();

    // iterate over the words
    foreach ($wordsPerPage[$pageNo] AS $_word) {
        // check for a match
        if ($_word['string'] !== $word) {
            continue;
        }

        // if a match occurs, create a highlight annotation and add it to the pages annotations instance
        $bounds = $_word['bounds'];
        foreach ($bounds AS $bound) {
            $rect = new Rectangle($bound->getLl(), $bound->getUr());
            $rect = RectangleDataStructure::byRectangle($rect);

            $annotation = new HighlightAnnotation($rect);
            $annotation->setColor([1, 1, 0]);
            $annotation->setContents('Match #' . (++$found));
            $annotation->setPrintFlag();
            $annotations->add($annotation);
        }
    }
}

// save the resulting document
$document->save()->finish();
