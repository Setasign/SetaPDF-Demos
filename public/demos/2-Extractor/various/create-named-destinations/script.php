<?php

use setasign\SetaPDF2\Core\DataStructure\Tree\KeyAlreadyExistsException;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Catalog\Names;
use setasign\SetaPDF2\Core\Document\Destination;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\FontSizeFilter;
use setasign\SetaPDF2\Extractor\Result\Word;
use setasign\SetaPDF2\Extractor\Strategy\WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/misc/Chapters.pdf',
    new HttpWriter('result.pdf', true)
);

$extractor = new Extractor($document);

// define the word strategy
$strategy = new WordStrategy();
// let's limit the result by a font size filter to speed things up
$filter = new FontSizeFilter(
    14,
    FontSizeFilter::MODE_LARGER_OR_EQUALS
);
$strategy->setFilter($filter);
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// get access to the named destination tree
$names = $document->getCatalog()->getNames()->getTree(Names::DESTS, true);

for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var Word[] $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);
    $chapter = null;

    // iterate over all found words and search for "Chapter" followed by a numeric string...
    foreach ($words AS $word) {
        $string = $word->getString();
        if ($string === 'Chapter') {
            $chapter = $word;
            continue;
        }

        if ($chapter === null) {
            continue;
        }

        // is the next word a numeric string
        if (is_numeric($word->getString())) {
            // get the coordinates of the word
            $bounds = $word->getBounds()[0];
            // create a destination
            $destination = Destination::createByPageNo(
                $document,
                $pageNo,
                Destination::FIT_MODE_FIT_BH,
                $bounds->getUl()->getY()
            );

            // create a name (shall be unique)
            $name = strtolower($chapter . $word->getString());
            if (!isset($_GET['dl'])) {
                echo '<a href="?dl=1#' . urlencode($name) . '">Link to &quot;' . htmlspecialchars($name) . '&quot;</a>'
                    . '<br />';
            }
            try {
                // add the named destination to the name tree
                $names->add($name, $destination->getPdfValue());
            } catch (KeyAlreadyExistsException $e) {
                echo 'The destination name "' . $name . '" is not unique.<br />';
                die();
            }
        }

        $chapter = null;
    }
}

if (isset($_GET['dl'])) {
    // save and finish the resulting document
    $document->save()->finish();
}
