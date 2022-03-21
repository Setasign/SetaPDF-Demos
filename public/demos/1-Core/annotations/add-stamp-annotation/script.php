<?php

use SetaPDF_Core_Document_Page_Annotation_Stamp as StampAnnotation;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$iconNameToPageNo = [
    StampAnnotation::ICON_APPROVED => 1,
    StampAnnotation::ICON_NOT_APPROVED => 18,
    StampAnnotation::ICON_EXPERIMENTAL => 20,
    StampAnnotation::ICON_AS_IS => 21,
    StampAnnotation::ICON_EXPIRED => 7,
    StampAnnotation::ICON_NOT_FOR_PUBLIC_RELEASE => 14,
    StampAnnotation::ICON_CONFIDENTIAL => 9,
    StampAnnotation::ICON_FINAL => 6,
    StampAnnotation::ICON_SOLD => 23,
    StampAnnotation::ICON_DEPARTMENTAL => 22,
    StampAnnotation::ICON_FOR_COMMENT => 11,
    StampAnnotation::ICON_TOP_SECRET => 19,
    StampAnnotation::ICON_DRAFT => 8,
    StampAnnotation::ICON_FOR_PUBLIC_RELEASE => 15,
    'Verified' => 2,
    'Revised' => 3,
    'Reviewed' => 4,
    'Received' => 5,
    'Completed' => 10,
    'InformationOnly' => 12,
    'PreliminaryResults' => 13,
    'Void' => 16,
    'Emergency' => 17
];

$iconName = displaySelect('Icon Name:', $iconNameToPageNo, true, true);
$x = 50;
$y = 700;
$width = 180;

$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = new SetaPDF_Core_Document($writer);

$page = $document->getCatalog()->getPages()->create(SetaPDF_Core_PageFormats::A4);

$stampAppearances = SetaPDF_Core_Document::loadByFilename($assetsDirectory . '/pdfs/stamps.pdf');
$appearancePageNo = $iconNameToPageNo[$iconName];
if (isset($appearancePageNo)) {
    $appearance = $stampAppearances->getCatalog()->getPages()->getPage($appearancePageNo)->toXObject($document);
    $height = $appearance->getHeight($width);

    $annotation = new StampAnnotation([$x, $y, $x + $width, $y + $height]);
    $annotation->setIconName($iconName);
    $annotation->setAppearance($appearance);
    $annotation->setName(uniqid('', true));

    $page->getAnnotations()->add($annotation);
}

$document->save()->finish();
