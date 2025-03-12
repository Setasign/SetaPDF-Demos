<?php

use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$main = $assetsDirectory . '/pdfs/Brand-Guide.pdf';
$ad = $assetsDirectory . '/pdfs/Setasign-Ebook-Ad.pdf';

$showAdAfterPages = 4;

// create a merger instance
$merger = new Merger();

// let's count the pages of the main document
// (document instance will be cached internally already then)
$pageCount = $merger->getPageCount($main);

for ($start = 1; $start < $pageCount; $start += $showAdAfterPages) {
    // add pages of the main document
    $merger->addFile([
        'filename' => $main,
        'pages' => $start . '-' . ($start + $showAdAfterPages - 1),
        // we only want to copy the outlines ones
        'outlinesConfig' => ($start === 1) ? [
            Merger::OUTLINES_COPY => Merger::COPY_OUTLINES_TO_ROOT
        ] : null
    ]);

    // add the ad only when enough pages were merged
    if (($start + $showAdAfterPages - 1) <= $pageCount) {
        $merger->addFile($ad);
    }
}

// merger
$merger->merge();

// get the resulting document instance
$document = $merger->getDocument();

// copy all metadata from the first document to the new one
$documentA = $merger->getDocumentByFileName($main);
$document->getInfo()->setAll($documentA->getInfo()->getAll());

$document->setWriter(new HttpWriter('insert-pages.pdf', true));
$document->save()->finish();
