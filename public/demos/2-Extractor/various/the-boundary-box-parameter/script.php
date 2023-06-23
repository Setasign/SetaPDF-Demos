<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$boxes = [
    \SetaPDF_Core_PageBoundaries::MEDIA_BOX,
    \SetaPDF_Core_PageBoundaries::CROP_BOX,
    \SetaPDF_Core_PageBoundaries::BLEED_BOX,
    \SetaPDF_Core_PageBoundaries::TRIM_BOX,
    \SetaPDF_Core_PageBoundaries::ART_BOX,
];

$boundaryBox = displaySelect('Page Boundary box:', $boxes);

$path = $assetsDirectory . '/pdfs/misc/Page-Boundaries.pdf';

$document = \SetaPDF_Core_Document::loadByFilename($path);

$extractor = new SetaPDF_Extractor($document);

$result = $extractor->getResultByPageNumber(1, $boxes[$boundaryBox]);

echo '<pre>';
echo htmlspecialchars($result);
echo '</pre>';
