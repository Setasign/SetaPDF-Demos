<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Annotation;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Widget;
use setasign\SetaPDF2\Core\Geometry\Matrix;
use setasign\SetaPDF2\Core\Geometry\Rectangle;
use setasign\SetaPDF2\Core\Geometry\Vector;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/products/Noisy-Tube-annotated.pdf',
    $assetsDirectory . '/pdfs/etown/Fact-Sheet-letterhead-as-annotation.pdf',
    $assetsDirectory . '/pdfs/camtown/Fact-Sheet-letterhead-as-annotation.pdf'
];

// if the SetaPDF-FormFiller component is installed add a demo document with a signature field
if (class_exists(FormFiller::class)) {
    $files[] = $assetsDirectory . '/pdfs/tektown/Laboratory-Report - commented-and-signed.pdf';
    $files[] = $assetsDirectory . '/pdfs/tektown/Order-Form-filled.pdf';
    $files[] = $assetsDirectory . '/pdfs/tektown/Subscription-tekMag-filled.pdf';
}

$path = displayFiles($files);

$writer = new HttpWriter('flatten-annotations.pdf', true);
$document = Document::loadByFilename($path, $writer);

$pages = $document->getCatalog()->getPages();
$pageCount = $pages->count();

function flattenAnnotation(Page $page, Annotation $annotation)
{
    $appearance = $annotation->getAppearance();
    if (
        $appearance === null
        || $appearance->getWidth() <= 0
        || $appearance->getHeight() <= 0
        || $annotation->getHiddenFlag()
        || $annotation->getInvisibleFlag()
    ) {
        return;
    }

    $canvas = $page->getCanvas();
    $page->getContents()->encapsulateExistingContentInGraphicState();
    $rect = $annotation->getRect()->getRectangle();
    $bbox = $appearance->getBBox()->getRectangle();

    $canvas->saveGraphicState();
    $canvas->write(' 0 J 1 w 0 j 0 G 0 g [] 0 d ');

    $matrix = $appearance->getMatrix();
    if ($matrix === false) {
        $matrix = new Matrix();
    }

    $t = new Rectangle(
        Vector::byPoint($bbox->getLl())->multiply($matrix)->toPoint(),
        Vector::byPoint($bbox->getUr())->multiply($matrix)->toPoint()
    );

    if (empty($t->getHeight()) || empty($t->getWidth())) {
        return;
    }

    $ll = $rect->getLl();

    $m = new Matrix(1, 0, 0, 1, $ll->getX(), $ll->getY());
    $scaleMatrix = new Matrix(
        ($rect->getWidth()) / ($t->getWidth()),
        0,
        0,
        ($rect->getHeight()) / ($t->getHeight()),
        0,
        0
    );
    $m = $scaleMatrix->multiply($m);
    $translateMatrix2 = new Matrix(1, 0, 0, 1, -$t->getLl()->getX(), -$t->getLl()->getY());
    $m = $translateMatrix2->multiply($m);

    $canvas->addCurrentTransformationMatrix(
        $m->getA(),
        $m->getB(),
        $m->getC(),
        $m->getD(),
        $m->getE(),
        $m->getF()
    );

    $appearance->ensureDefaultKeys();
    $canvas->drawXObject($appearance);

    $canvas->restoreGraphicState();
}

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $page = $pages->getPage($pageNo);
    $annotations = $page->getAnnotations();
    $allAnnotations = $annotations->getAll();

    foreach ($allAnnotations as $k => $annotation) {
        if ($annotation instanceof Widget) {
            continue;
        }

        try {
            flattenAnnotation($page, $annotation);
        } catch (Exception $e) {
            // there was a problem flattening this annotation
        }
        $annotations->remove($annotation);
    }
}

// if the SetaPDF-FormFiller component is installed we are going to flatten form fields, too:
if (class_exists(FormFiller::class)) {
    $formFiller = new FormFiller($document);
    $formFiller->getFields()->flatten();
}

$document->save()->finish();
