<?php

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
$writer = new SetaPDF_Core_Writer_Http('links-replaced.pdf', true);
// create a document
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

// Get the pages helper
$pages = $document->getCatalog()->getPages();

$linksFound = false;
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $page = $pages->getPage($pageNo);
    $linkAnnotations = $page->getAnnotations()->getAll(SetaPDF_Core_Document_Page_Annotation::TYPE_LINK);

    /** @var SetaPDF_Core_Document_Page_Annotation_Link $linkAnnotation */
    foreach ($linkAnnotations AS $linkAnnotation) {
        $action = $linkAnnotation->getAction();
        if ($action && $action instanceof SetaPDF_Core_Document_Action_Uri) {
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
