<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/lenstown/Order-Form.pdf',
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
    $assetsDirectory . '/pdfs/etown/Terms-and-Conditions.pdf',
];

$file = displayFiles($files);
$dpi = 72;

$document = \SetaPDF_Core_Document::loadByFilename($file);
$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

$pages = $document->getCatalog()->getPages();

for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    $imageFile = 'images/' . basename($file, '.pdf') . '-' . $pageNo . '-' . $dpi . '.png';

    if (!file_exists($imageFile)) {
        $cmd = 'mutool draw -F png -r ' . escapeshellarg($dpi) . ' -o ' . escapeshellarg($imageFile)
            . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($pageNo);

        exec($cmd, $ouput, $resultCode);

        if ($resultCode !== 0) {
            echo 'Thumbnail could not be generated. Please make sure that ' .
                '<a href="https://www.mupdf.com/docs/manual-mutool-draw.html" target="_blank">mutool</a> is installed ' .
                'and that the images/ folder is writable.';
            die();
        }
    }

    $document = \SetaPDF_Core_Document::loadByFilename($file);
    $page = $document->getCatalog()->getPages()->getPage($pageNo);

    // this is the factor between points and px
    $dpiFactor = 1/72 * $dpi;

    // get some general information
    $pageWidth = $page->getWidth() * $dpiFactor;
    $pageHeight = $page->getHeight() * $dpiFactor;
    $rotation = $page->getRotation();

    // now we create a graphic state instance
    $gs = new \SetaPDF_Core_Canvas_GraphicState();
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
    $f = static function(\SetaPDF_Core_Geometry_Point $p) use ($gs) {
        $v = new \SetaPDF_Core_Geometry_Vector($p->getX(), $p->getY(), 0);
        return $v->multiply($gs->getCurrentTransformationMatrix())->toPoint();
    };

    // this helper draws the rect of a form field
    $renderFieldRect = function(SetaPDF_FormFiller_Field_AbstractField $field, $fieldName) use ($f)
    {
        $rect = $field->getAnnotation()->getRect()->getRectangle();
        $rect = new \SetaPDF_Core_Geometry_Rectangle(
            $f($rect->getLl()),
            $f($rect->getUr())
        );

        echo '<div style="position:absolute;background-color:rgba(0, 54, 255, 0.13);' .
            'left:' . $rect->getLl()->getX() . 'px;' .
            'bottom:' . $rect->getLl()->getY() . 'px;' .
            'width:' . ($rect->getWidth()) . 'px;height: ' . ($rect->getHeight()) . 'px;' .
            '" title="' . htmlspecialchars($fieldName). '"></div>';
    };

    ?>
    <div style="position: relative; width: <?=$pageWidth?>px; height: <?=$pageHeight?>;
            border: 1px solid lightgrey; margin-bottom: 5px;"
    >
        <img src="<?=$imageFile?>" style="position: absolute; width: <?=$pageWidth?>px; height: <?=$pageHeight?>;" />
        <?php
        /**
         * @var string $fieldName
         * @var SetaPDF_FormFiller_Field_FieldInterface $field
         */
        foreach ($fields->getAll() as $fieldName => $field) {
            if ($field instanceof SetaPDF_FormFiller_Field_AbstractField) {
                if ($field->getPageNumber() === $pageNo) {
                    $renderFieldRect($field, $fieldName);
                }
            } elseif ($field instanceof SetaPDF_FormFiller_Field_ButtonGroup) {
                foreach ($field->getButtons() as $button) {
                    if ($button->getPageNumber() === $pageNo) {
                        $renderFieldRect($button, $fieldName);
                    }
                }
            }
        }
        ?>
    </div>
    <?php
}
