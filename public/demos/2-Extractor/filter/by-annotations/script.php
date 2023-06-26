<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions - revised.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide - with-comments.pdf',
];

$path = displayFiles($files);

// create a document instance
$document = \SetaPDF_Core_Document::loadByFilename($path);
// initate an extractor instance
$extractor = new \SetaPDF_Extractor($document);

// get page documents pages object
$pages = $document->getCatalog()->getPages();

// we are going to save the extracted text in this variable
$results = [];
// map pages and filternames to annotation instances
$annotationsByPageAndFilterName = [];

// iterate over all pages
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    // get the page object
    $page = $pages->getPage($pageNo);
    // get the annotations
    $annotations = array_filter(
        $page->getAnnotations()->getAll(),
        static function (\SetaPDF_Core_Document_Page_Annotation $annotation) {
            switch ($annotation->getType()) {
                case \SetaPDF_Core_Document_Page_Annotation::TYPE_HIGHLIGHT:
                case \SetaPDF_Core_Document_Page_Annotation::TYPE_STRIKE_OUT:
                case \SetaPDF_Core_Document_Page_Annotation::TYPE_CARET:
                case \SetaPDF_Core_Document_Page_Annotation::TYPE_UNDERLINE:
                    return true;
            }

            return false;
        }
    );

    // create a strategy instance
    $strategy = new \SetaPDF_Extractor_Strategy_ExactPlain();
    // create a multi filter instance
    $filter = new \SetaPDF_Extractor_Filter_Multi();
    // and pass it to the strategy
    $strategy->setFilter($filter);

    // iterate over all highlight annotations
    foreach ($annotations AS $tmpId => $annotation) {
        /**
         * @var \SetaPDF_Core_Document_Page_Annotation_TextMarkup $annotation
         */
        $name = 'P#' . $pageNo . '/TMA#' . $tmpId;
        if ($annotation->getName()) {
            $name .= ' (' . $annotation->getName() . ')';
        }

        if ($annotation instanceof \SetaPDF_Core_Document_Page_Annotation_TextMarkup) {
            // iterate over the quad points to setup our filter instances
            $quadpoints = $annotation->getQuadPoints();
            for ($pos = 0, $c = count($quadpoints); $pos < $c; $pos += 8) {
                $llx = min($quadpoints[$pos + 0], $quadpoints[$pos + 2], $quadpoints[$pos + 4], $quadpoints[$pos + 6]) - 1;
                $urx = max($quadpoints[$pos + 0], $quadpoints[$pos + 2], $quadpoints[$pos + 4], $quadpoints[$pos + 6]) + 1;
                $lly = min($quadpoints[$pos + 1], $quadpoints[$pos + 3], $quadpoints[$pos + 5], $quadpoints[$pos + 7]) - 1;
                $ury = max($quadpoints[$pos + 1], $quadpoints[$pos + 3], $quadpoints[$pos + 5], $quadpoints[$pos + 7]) + 1;

                // reduze it to a small line
                $diff = ($ury - $lly) / 2;
                $lly = $lly + $diff - 1;
                $ury = $ury - $diff - 1;

                // Add a new rectangle filter to the multi filter instance
                $filter->addFilter(
                    new \SetaPDF_Extractor_Filter_Rectangle(
                        new \SetaPDF_Core_Geometry_Rectangle($llx, $lly, $urx, $ury),
                        \SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT,
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
        echo '<td><pre>' . htmlspecialchars(
            isset($results[$pageNo][$name]) ? $results[$pageNo][$name] : ''
            ) . '</pre></td>';
        echo '<td><pre>' . htmlspecialchars($annotation->getSubject()) . '</pre></td>';
        echo '<td><pre>' . htmlspecialchars($annotation->getContents()) . '</pre></td>';
        echo '</tr>';
    }

    echo '</table>';
}
