<?php

use setasign\SetaPDF2\Core\Canvas\GraphicState;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Geometry\Point;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Core\Geometry\Vector;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Words;
use setasign\SetaPDF2\Extractor\Strategy\Word as WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/misc/boxes/[1000 500 -1000 -500].pdf',
    $assetsDirectory . '/pdfs/misc/boxes/[1000 500 -1000 -500]-R90.pdf',
    $assetsDirectory . '/pdfs/misc/boxes/[1000 500 -1000 -500]-R-90.pdf',
    $assetsDirectory . '/pdfs/misc/rotated/90.pdf',
    $assetsDirectory . '/pdfs/misc/rotated/180.pdf',
    $assetsDirectory . '/pdfs/misc/rotated/270.pdf',
];

$file = displayFiles($files);
$dpi = 72;
$pageNo = 1;
$imageFile = 'images/' . basename($file, '.pdf') . '-' . $dpi . '.png';

if (!file_exists($imageFile)) {
    $cmd = 'mutool draw -F png -r ' . escapeshellarg($dpi) . ' -o ' . escapeshellarg($imageFile)
         . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($pageNo);

    exec($cmd, $output, $resultCode);

   if ($resultCode !== 0) {
       echo 'Thumbnail could not be generated. Please make sure that ' .
            '<a href="https://www.mupdf.com/docs/manual-mutool-draw.html" target="_blank">mutool</a> is installed ' .
            'and that the images/ folder is writable.';
       die();
   }
}

$document = Document::loadByFilename($file);
$page = $document->getCatalog()->getPages()->getPage($pageNo);

$extractor = new Extractor($document);
$extractor->setStrategy(new WordStrategy());

/** @var Words $words */
$words = $extractor->getResultByPageNumber(1);

// this is the factor between points and px
$dpiFactor = 1/72 * $dpi;

// get some general information
$pageWidth = $page->getWidth() * $dpiFactor;
$pageHeight = $page->getHeight() * $dpiFactor;
$rotation = $page->getRotation();

// now we create a graphic state instance
$gs = new GraphicState();
// scale it by the DPI factor
$gs->scale($dpiFactor, $dpiFactor);

$box = $page->getCropBox();
// translate it in view to the crop box origin
$gs->translate(-$box->llx, -$box->lly);
// rotate it
$gs->rotate($box->llx, $box->lly, -$rotation);

// depending on the rotation value, translate back
switch ($rotation) {
    case 90:
        $gs->translate(-$box->getWidth(), 0);
        break;
    case 180:
        $gs->translate(-$box->getWidth(), -$box->getHeight());
        break;
    case 270:
        $gs->translate(0, -$box->getHeight());
        break;
}

// this little helper applies the graphic state to a given point
$f = static function(Point $p) use ($gs) {
    $v = new Vector($p->getX(), $p->getY(), 0);
    return $v->multiply($gs->getCurrentTransformationMatrix())->toPoint();
};

echo <<< HTML
<div style="position: relative; width: {$pageWidth}px; height: {$pageHeight}px;border: 1px solid lightgrey;">
    <img src="$imageFile" style="position: absolute; width: {$pageWidth}px; height: {$pageHeight}px;"/>
HTML;

foreach ($words as $word) {
    foreach ($word->getBounds() as $bounds) {
        // create a rectangle with ordered vertices (because e.g. Ll will not be lower left after a rotation)
        $rect = new Rectangle(
            $f($bounds->getLl()),
            $f($bounds->getUr())
        );

        echo '<div style="position:absolute;border:1px solid #ff00ff;background-color:#ff00ff3C;' .
            'left:' . $rect->getLl()->getX() . 'px;' .
            'bottom:' . $rect->getLl()->getY() . 'px;' .
            'width:' . ($rect->getWidth()) . 'px;height: ' . ($rect->getHeight()) . 'px;' .
            '" title="' . htmlspecialchars($word->getString()). '"></div>';
    }
}

echo '</div>';
