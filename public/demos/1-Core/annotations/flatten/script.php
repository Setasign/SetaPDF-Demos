<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/products/Noisy-Tube-annotated.pdf',
    $assetsDirectory . '/pdfs/etown/Fact-Sheet-letterhead-as-annotation.pdf',
    $assetsDirectory . '/pdfs/camtown/Fact-Sheet-letterhead-as-annotation.pdf'
];

// if the SetaPDF-FormFiller component is installed add a demo document with a signature field
if (class_exists(SetaPDF_FormFiller::class)) {
    $files[] = $assetsDirectory . '/pdfs/tektown/Laboratory-Report - commented-and-signed.pdf';
}

$path = displayFiles($files);

$writer = new SetaPDF_Core_Writer_Http('flatten-annotations.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename($path, $writer);

$pages = $document->getCatalog()->getPages();
$pageCount = $pages->count();

function flattenAnnotation(SetaPDF_Core_Document_Page $page, SetaPDF_Core_Document_Page_Annotation $annotation)
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
        $matrix = new SetaPDF_Core_Geometry_Matrix();
    }

    $t = new SetaPDF_Core_Geometry_Rectangle(
        SetaPDF_Core_Geometry_Vector::byPoint($bbox->getLl())->multiply($matrix)->toPoint(),
        SetaPDF_Core_Geometry_Vector::byPoint($bbox->getUr())->multiply($matrix)->toPoint()
    );

    if (empty($t->getHeight()) || empty($t->getWidth())) {
        return;
    }

    $ll = $rect->getLl();

    $m = new SetaPDF_Core_Geometry_Matrix(1, 0, 0, 1, $ll->getX(), $ll->getY());
    $scaleMatrix = new SetaPDF_Core_Geometry_Matrix(
        ($rect->getWidth()) / ($t->getWidth()),
        0,
        0,
        ($rect->getHeight()) / ($t->getHeight()),
        0,
        0
    );
    $m = $scaleMatrix->multiply($m);
    $translateMatrix2 = new SetaPDF_Core_Geometry_Matrix(1, 0, 0, 1, -$t->getLl()->getX(), -$t->getLl()->getY());
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
        if ($annotation instanceof SetaPDF_Core_Document_Page_Annotation_Widget) {
            continue;
        }

        flattenAnnotation($page, $annotation);
        $annotations->remove($annotation);
    }
}

// if the SetaPDF-FormFiller component is installed we are going to flatten form fields, too:
if (class_exists(SetaPDF_FormFiller::class)) {
    $formFiller = new SetaPDF_FormFiller($document);
    $formFiller->getFields()->flatten();
}

$document->save()->finish();
