<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Filter\RectangleFilter;
use setasign\SetaPDF2\Extractor\Strategy\ExactPlainStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/lenstown/Subscription-tekMag.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf'
];

$path = displayFiles($files);

$document = Document::loadByFilename($path);
$extractor = new Extractor($document);

$strategy = new ExactPlainStrategy();
$extractor->setStrategy($strategy);

// we prepare an initial filter to reduce the text item count
$mainFilter = new RectangleFilter(new Rectangle(0, 752, 596, 649));
$strategy->setFilter($mainFilter);

$filters = [
    'Laboratory Report' => new RectangleFilter(new Rectangle(36, 712, 240, 674)),
    'Terms and Conditions' => new RectangleFilter(new Rectangle(32, 716, 286, 671)),
    'Subscription tekMag' => new RectangleFilter(new Rectangle(31, 713, 262, 672))
];

$page = $document->getCatalog()->getPages()->getPage(1);
$textItems = $extractor->getTextItemsByPage($page);

foreach ($filters as $filterName => $filter) {
    // tell the filter about the page, the text-items came from
    $filter->setPage($page);
    // now we filter the existing text-items by the individual filter
    $result = $strategy->getResultByTextItems($textItems, $filter);
    if ($result === $filterName) {
        $match = $filterName;
        break;
    }
}

switch ($match ?? null) {
    case 'Laboratory Report':
        echo 'This document is a laboratory report!';
        // ...process it
        break;
    case 'Terms and Conditions':
        echo 'This document shows Terms and Conditions!';
        // ...process it
        break;
    case 'Subscription tekMag':
        echo 'This document is a subscription form to the tekMag!';
        // ...process it
        break;
    default:
        echo 'Sorry, but I cannot recognize the document type.';
}
