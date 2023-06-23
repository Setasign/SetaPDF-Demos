<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/misc/Chapters.pdf',
    new \SetaPDF_Core_Writer_Http('result.pdf', true)
);

$extractor = new SetaPDF_Extractor($document);

// define the word strategy
$strategy = new SetaPDF_Extractor_Strategy_Word();
// let's limit the result by a font size filter to speed things up
$filter = new SetaPDF_Extractor_Filter_FontSize(
    14,
    SetaPDF_Extractor_Filter_FontSize::MODE_LARGER_OR_EQUALS
);
$strategy->setFilter($filter);
$extractor->setStrategy($strategy);

// get the pages helper
$pages = $document->getCatalog()->getPages();

// get access to the named destination tree
$names = $document->getCatalog()->getNames()->getTree(
    \SetaPDF_Core_Document_Catalog_Names::DESTS, true
);

for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var SetaPDF_Extractor_Result_Word[] $words
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
            $destination = \SetaPDF_Core_Document_Destination::createByPageNo(
                $document,
                $pageNo,
                \SetaPDF_Core_Document_Destination::FIT_MODE_FIT_BH,
                $bounds->getUl()->getY()
            );

            // create a name (shall be unique)
            $name = strtolower($chapter . $word->getString());
            if (!isset($_GET['dl'])) {
                echo '<a href="?dl=1#' . urlencode($name) .
                    '">Link to &quot;' . htmlspecialchars($name) . '&quot;</a><br />';
            }
            try {
                // add the named destination to the name tree
                $names->add($name, $destination->getPdfValue());
            } catch (\SetaPDF_Core_DataStructure_Tree_KeyAlreadyExistsException $e) {
                echo 'The destination name "' . $name . "\" is not unique.<br />";
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
