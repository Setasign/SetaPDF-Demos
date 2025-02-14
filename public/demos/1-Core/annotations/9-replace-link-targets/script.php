<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\UriAction;
use setasign\SetaPDF2\Core\Document\Page\Annotation;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/lenstown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/tektown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/camtown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf',
];

$path = displayFiles($files);

// create a writer
$writer = new HttpWriter('links-replaced.pdf', true);
// create a document
$document = Document::loadByFilename($path, $writer);

// Get the pages helper
$pages = $document->getCatalog()->getPages();

$linksFound = false;
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $page = $pages->getPage($pageNo);
    $linkAnnotations = $page->getAnnotations()->getAll(Annotation::TYPE_LINK);

    /** @var \setasign\SetaPDF2\Core\Document\Page\Annotation\Link $linkAnnotation */
    foreach ($linkAnnotations AS $linkAnnotation) {
        $action = $linkAnnotation->getAction();
        if ($action && $action instanceof UriAction) {
            // simply set the new URI
            $action->setUri('https://www.setasign.com');
            $linksFound = true;
            break;
        }
    }
}

if ($linksFound) {
    $document->save()->finish();
} else {
    echo 'No links found!';
}
