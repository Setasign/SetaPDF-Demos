<?php

use SetaPDF_Core_Document_Page_Annotation_Stamp as StampAnnotation;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$pageConfigs = [
    ['0deg, A4', 0, SetaPDF_Core_PageFormats::A4],
    ['90deg, A4', 90, SetaPDF_Core_PageFormats::A4],
    ['180deg, A4', 180, SetaPDF_Core_PageFormats::A4],
    ['270deg, A4', 270, SetaPDF_Core_PageFormats::A4],
    ['0deg, [-100, -100, 350, 700]', 0, [-100, -100, 350, 700]],
    ['90deg, [-100, -200, 350, 800]', 90, [-100, -200, 350, 800]],
    ['180deg, [-100, -200, 350, 800]', 180, [-100, -200, 350, 800]],
    ['270deg, [-100, -200, 350, 800]', 270, [-100, -200, 350, 800]],
    ['0deg, [100, 100, 550, 900]', 0, [100, 100, 550, 900]],
    ['90deg, [100, 100, 550, 900]', 90, [100, 100, 550, 900]],
    ['180deg, [100, 100, 550, 900]', 180, [100, 100, 550, 900]],
    ['270deg, [100, 100, 550, 900]', 270, [100, 100, 550, 900]],
    ['0deg, [550, 900, 100, 100]', 0, [550, 900, 100, 100]],
    ['90deg, [350, 800, -100, -200]', 90, [350, 800, -100, -200]],
];

$pageConfig = displaySelect('Page rotation and format:', $pageConfigs);

$x = 20;
$yTop = 30; // let's take the upper left as the origin
$width = 180;
$rotation = $pageConfigs[$pageConfig][1];

$writer = new SetaPDF_Core_Writer_Http('rotated.pdf', true);
$document = new SetaPDF_Core_Document($writer);
$document->getCatalog()->setPageLayout(SetaPDF_Core_Document_PageLayout::SINGLE_PAGE);

// let's create a dummy page with the expected format and rotation
$page = $document->getCatalog()->getPages()->create($pageConfigs[$pageConfig][2]);
$page->setRotation($rotation);

// normally you will get the rotation value from an existing page:
$rotation = $page->getRotation();

// let's get an appearance by using a page of an existing PDF
$stampAppearances = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/stamps.pdf')
    ->getCatalog()->getPages()->getPage(1)->toXObject($document);
$height = $stampAppearances->getHeight($width);

// calculate the ordinate
$y = $page->getHeight() - $height - $yTop;

// depending on the rotation value we need to prepare a graphic state for the position on the page
$box = $page->getBoundary();

$pageGs = new SetaPDF_Core_Canvas_GraphicState();
switch ($rotation) {
    case 90:
        $pageGs->translate($box->getWidth(), 0);
        break;
    case 180:
        $pageGs->translate($box->getWidth(), $box->getHeight());
        break;
    case 270:
        $pageGs->translate(0, $box->getHeight());
        break;
}

$pageGs->rotate($box->llx, $box->lly, $rotation);
$pageGs->translate($box->llx, $box->lly);

// let's create a helper function to translate coordinates into vectors by using the page graphic state
$f = static function($x, $y) use ($pageGs) {
    $v = new SetaPDF_Core_Geometry_Vector($x, $y);
    return $v->multiply($pageGs->getCurrentTransformationMatrix());
};

$ll = $f($x, $y);
$ur = $f($x + $width, $y + $height);

$annotation = new StampAnnotation([$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()]);
$annotation->setIconName(StampAnnotation::ICON_APPROVED);

// now we create a new form XObject where we draw the final appearance into
$appearance = SetaPDF_Core_XObject_Form::create($document, [0, 0, $annotation->getWidth(), $annotation->getHeight()]);

$canvas = $appearance->getCanvas();
$canvas->saveGraphicState();
$canvas->normalizeRotation($rotation, $appearance->getBBox());
$stampAppearances->draw($canvas, 0, 0, $width, $height);
$canvas->restoreGraphicState();

$annotation->setAppearance($appearance);
$annotation->setName(uniqid('', true));

$page->getAnnotations()->add($annotation);

$document->save()->finish();
