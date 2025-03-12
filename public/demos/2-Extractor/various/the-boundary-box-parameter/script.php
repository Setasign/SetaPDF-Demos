<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Extractor\Extractor;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$boxes = [
    PageBoundaries::MEDIA_BOX,
    PageBoundaries::CROP_BOX,
    PageBoundaries::BLEED_BOX,
    PageBoundaries::TRIM_BOX,
    PageBoundaries::ART_BOX,
];

$boundaryBox = displaySelect('Page Boundary box:', $boxes);

$path = $assetsDirectory . '/pdfs/misc/Page-Boundaries.pdf';

$document = Document::loadByFilename($path);

$extractor = new Extractor($document);

$result = $extractor->getResultByPageNumber(1, $boxes[$boundaryBox]);

echo '<pre>';
echo htmlspecialchars($result);
echo '</pre>';
