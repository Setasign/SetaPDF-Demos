<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Catalog\AcroForm;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Annotation;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\MultiFilter;
use setasign\SetaPDF2\Extractor\Filter\RectangleFilter;
use setasign\SetaPDF2\Extractor\Strategy\PlainStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// Let's load a template in which we'd drawn simple form fields to get the names and
// coordinates of the areas we want to extract
$template = Document::loadByFilename($assetsDirectory . '/pdfs/Subscription-tekMag-form-template.pdf');
$pages = $template->getCatalog()->getPages();

// group the found fields by pages
$fieldsPerPage = [];

for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $fieldsPerPage[$pageNo] = [];
    $page = $pages->getPage($pageNo);
    $annotations = $page->getAnnotations();

    // get all widget annotations
    $widgetAnnotations = $annotations->getAll(Annotation::TYPE_WIDGET);
    foreach ($widgetAnnotations AS $widgetAnnotation) {
        $fieldName = AcroForm::resolveFieldName($widgetAnnotation->getDictionary());
        $fieldsPerPage[$pageNo][$fieldName] = $widgetAnnotation->getRect()->getRectangle();
    }
}

// clean up
$template->cleanUp();
unset($page, $pages, $template);

// let's extract the data from these files...
foreach ([
     $assetsDirectory . '/pdfs/lenstown/Subscription-tekMag-filled-flat.pdf',
     $assetsDirectory . '/pdfs/camtown/Subscription-tekMag-filled-flat.pdf',
     $assetsDirectory . '/pdfs/etown/Subscription-tekMag-filled-flat.pdf',
] AS $path) {

    echo '<h1>' . htmlspecialchars(substr($path, strlen($assetsDirectory . '/pdfs/'))) . '</h1>';

    // load the document
    $document = Document::loadByFilename($path);

    // create a plain strategy
    $strategy = new PlainStrategy();

    // create an extractor instance
    $extractor = new Extractor($document, $strategy);

    // iterate through the pages we want to extract data from.
    foreach ($fieldsPerPage AS $pageNo => $fields) {

        // define a multi filter
        $filter = new MultiFilter();
        // create additional rectangle filters named by the found fields and ...
        foreach ($fields AS $name => $rect) {
            $fieldFilter = new RectangleFilter(
                $rect, RectangleFilter::MODE_CONTACT, $name
            );

            // ...pass them to the multi filter
            $filter->addFilter($fieldFilter);
        }
        // set the filter
        $strategy->setFilter($filter);

        // get the result
        $result = $extractor->getResultByPageNumber($pageNo);

        // clean up the result because the values lay on top of other text which needs to be removed
        $result = array_map(function ($s) {
            $s = str_replace("\xef\x82\xa8", '', $s);
            return trim($s, "\n.");
        }, $result);

        echo "<pre>";
        var_dump($result);
        echo "</pre>";
    }
}