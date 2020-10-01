<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/tektown/products/Noisy-Tube-annotated.pdf',
    $assetsDirectory . '/pdfs/etown/Fact-Sheet-letterhead-as-annotation.pdf',
    $assetsDirectory . '/pdfs/camtown/Fact-Sheet-letterhead-as-annotation.pdf'
];

displayFiles($files);

$writer = new SetaPDF_Core_Writer_Http('flatten-annotations.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename($_GET['f'], $writer);

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
    $rect = $annotation->getRect();
    $bbox = $appearance->getBBox();

    $canvas->saveGraphicState();
    $canvas->write(' 0 J 1 w 0 j 0 G 0 g [] 0 d ');

    $matrix = $appearance->getMatrix();
    if ($matrix === false) {
        $matrix = new SetaPDF_Core_Geometry_Matrix();
    }

    $_rect = $rect->getRectangle();
    $t = SetaPDF_Core_DataStructure_Rectangle::byRectangle(
        new SetaPDF_Core_Geometry_Rectangle(
            SetaPDF_Core_Geometry_Vector::byPoint($_rect->getLl())->multiply($matrix)->toPoint(),
            SetaPDF_Core_Geometry_Vector::byPoint($_rect->getUr())->multiply($matrix)->toPoint()
        )
    );

    $m = new SetaPDF_Core_Geometry_Matrix(1, 0, 0, 1, $rect->llx, $rect->lly);
    $scaleMatrix = new SetaPDF_Core_Geometry_Matrix(
        ($rect->urx - $rect->llx) / ($t->urx - $t->llx),
        0,
        0,
        ($rect->ury - $rect->lly) / ($t->ury - $t->lly),
        0,
        0
    );
    $m = $scaleMatrix->multiply($m);
    $translateMatrix2 = new SetaPDF_Core_Geometry_Matrix(1, 0, 0, 1, -$t->llx, -$t->lly);
    $m = $translateMatrix2->multiply($m);

    $canvas->addCurrentTransformationMatrix(
        $m->getA(),
        $m->getB(),
        $m->getC(),
        $m->getD(),
        $m->getE(),
        $m->getF()
    );

    $canvas->drawXObject($appearance);

    $canvas->restoreGraphicState();
}

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $page = $pages->getPage($pageNo);
    $annotations = $page->getAnnotations();
    $allAnnotations = $annotations->getAll();

    foreach ($allAnnotations as $annotation) {
        if ($annotation instanceof SetaPDF_Core_Document_Page_Annotation_Widget) {
            continue;
        }

        flattenAnnotation($page, $annotation);
        $annotations->remove($annotation);
    }
}

$document->save()->finish();
