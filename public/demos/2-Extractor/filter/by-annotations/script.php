<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Annotation;
use setasign\SetaPDF2\Core\Document\Page\Annotation\TextMarkup;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\Multi as MultiFilter;
use setasign\SetaPDF2\Extractor\Filter\Rectangle as RectangleFilter;
use setasign\SetaPDF2\Extractor\Strategy\ExactPlain as ExactPlainStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions - revised.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide - with-comments.pdf',
];

$path = displayFiles($files);

// create a document instance
$document = Document::loadByFilename($path);
// initate an extractor instance
$extractor = new Extractor($document);

// get page documents pages object
$pages = $document->getCatalog()->getPages();

// we are going to save the extracted text in this variable
$results = [];
// map pages and filter names to annotation instances
$annotationsByPageAndFilterName = [];

// iterate over all pages
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    // get the page object
    $page = $pages->getPage($pageNo);
    // get the annotations
    $annotations = array_filter(
        $page->getAnnotations()->getAll(),
        static function (Annotation $annotation) {
            switch ($annotation->getType()) {
                case Annotation::TYPE_HIGHLIGHT:
                case Annotation::TYPE_STRIKE_OUT:
                case Annotation::TYPE_CARET:
                case Annotation::TYPE_UNDERLINE:
                    return true;
            }

            return false;
        }
    );

    // create a strategy instance
    $strategy = new ExactPlainStrategy();
    // create a multi filter instance
    $filter = new MultiFilter();
    // and pass it to the strategy
    $strategy->setFilter($filter);

    // iterate over all highlight annotations
    foreach ($annotations AS $tmpId => $annotation) {
        /**
         * @var TextMarkup $annotation
         */
        $name = 'P#' . $pageNo . '/TMA#' . $tmpId;
        if ($annotation->getName()) {
            $name .= ' (' . $annotation->getName() . ')';
        }

        if ($annotation instanceof TextMarkup) {
            // iterate over the quad points to setup our filter instances
            $quadPoints = $annotation->getQuadPoints();
            for ($pos = 0, $c = count($quadPoints); $pos < $c; $pos += 8) {
                $llx = min($quadPoints[$pos + 0], $quadPoints[$pos + 2], $quadPoints[$pos + 4], $quadPoints[$pos + 6]) - 1;
                $urx = max($quadPoints[$pos + 0], $quadPoints[$pos + 2], $quadPoints[$pos + 4], $quadPoints[$pos + 6]) + 1;
                $lly = min($quadPoints[$pos + 1], $quadPoints[$pos + 3], $quadPoints[$pos + 5], $quadPoints[$pos + 7]) - 1;
                $ury = max($quadPoints[$pos + 1], $quadPoints[$pos + 3], $quadPoints[$pos + 5], $quadPoints[$pos + 7]) + 1;

                // reduce it to a small line
                $diff = ($ury - $lly) / 2;
                $lly = $lly + $diff - 1;
                $ury = $ury - $diff - 1;

                // Add a new rectangle filter to the multi filter instance
                $filter->addFilter(
                    new RectangleFilter(
                        new Rectangle($llx, $lly, $urx, $ury),
                        RectangleFilter::MODE_CONTACT,
                        $name
                    )
                );
            }
        }

        $annotationsByPageAndFilterName[$pageNo][$name] = $annotation;
    }

    // if no filters for this page defined, ignore it
    if (count($filter->getFilters()) === 0) {
        continue;
    }

    // pass the strategy to the extractor instance
    $extractor->setStrategy($strategy);
    // and get the results by the current page number
    $result = $extractor->getResultByPageNumber($pageNo);
    if ($result === '') {
        continue;
    }

    $results[$pageNo] = $result;
}

// debug output
foreach ($annotationsByPageAndFilterName AS $pageNo => $annotations) {
    echo '<h1>Page No #' . $pageNo . '</h1>';
    echo '<table border="1" width="100%"><tr><th>Name</th><th>Text</th><th>Subject</th><th>Comment</th></tr>';
    foreach ($annotations AS $name => $annotation) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($name) . '</td>';
        echo '<td><pre>' . htmlspecialchars($results[$pageNo][$name] ?? '') . '</pre></td>';
        echo '<td><pre>' . htmlspecialchars($annotation->getSubject() ?? '') . '</pre></td>';
        echo '<td><pre>' . htmlspecialchars($annotation->getContents() ?? '') . '</pre></td>';
        echo '</tr>';
    }

    echo '</table>';
}
