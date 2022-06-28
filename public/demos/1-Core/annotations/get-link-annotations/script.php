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

// create a document
$document = SetaPDF_Core_Document::loadByFilename($path);

// Get the pages helper
$pages = $document->getCatalog()->getPages();

echo '<pre>';
$linksFound = false;
for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    $page = $pages->getPage($pageNo);
    $linkAnnotations = $page->getAnnotations()->getAll(SetaPDF_Core_Document_Page_Annotation::TYPE_LINK);

    /** @var SetaPDF_Core_Document_Page_Annotation_Link $linkAnnotation */
    foreach ($linkAnnotations AS $linkAnnotation) {
        $action = $linkAnnotation->getAction();
        $destination = $linkAnnotation->getDestination();
        if ($action || $destination) {
            echo 'Link Annotation on Page #' . $pageNo . "\n";
            if ($action instanceof SetaPDF_Core_Document_Action_Uri) {
                echo '     URI: ' . htmlspecialchars($action->getUri());
            } elseif ($action instanceof SetaPDF_Core_Document_Action_GoTo) {
                $destination = $action->getDestination($document);
            }

            if ($destination) {
                echo '     Destination: Page ' . $destination->getPageNo($document);
            }

            echo  "\n";
            $rect = $linkAnnotation->getRect();
            echo '     llx: ' . $rect->getLlx() . "\n";
            echo '     lly: ' . $rect->getLly() . "\n";
            echo '     urx: ' . $rect->getUrx() . "\n";
            echo '     ury: ' . $rect->getUry() . "\n";
            echo '   width: ' . $rect->getWidth() . "\n";
            echo '  height: ' . $rect->getHeight() . "\n\n";
            $linksFound = true;
        }
    }
}

if ($linksFound === false) {
    echo 'No links found!';
}
