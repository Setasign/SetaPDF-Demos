<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Catalog\AcroForm;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Annotation;
use setasign\SetaPDF2\Core\Document\Page\Annotation\WidgetAnnotation;
use setasign\SetaPDF2\Core\Encoding\Encoding;
use setasign\SetaPDF2\Core\Type\Dictionary\DictionaryHelper;
use setasign\SetaPDF2\Core\Type\PdfStringInterface;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/forms/Calculated-And-Formatted.pdf',
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf'
];

$path = displayFiles($files);

// create a document instance
$document = Document::loadByFilename($path);

// Get the pages helper
$pages = $document->getCatalog()->getPages();

for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
    // get a page instance
    $page = $pages->getPage($pageNo);
    echo '<h1>Page ' . $pageNo . '</h1>';

    // get the annotation helper
    $annotationsHelper = $page->getAnnotations();
    $widgetAnnotations = $annotationsHelper->getAll(Annotation::TYPE_WIDGET);
    echo '<p>' . count($widgetAnnotations) . ' widget annotations found.</p>';

    /* @var WidgetAnnotation $widgetAnnotation */
    foreach ($widgetAnnotations AS $widgetAnnotation) {
        $fieldName = AcroForm::resolveFieldName(
            $widgetAnnotation->getIndirectObject()->ensure()
        );

        echo $fieldName . ': <pre>';

        $rect = $widgetAnnotation->getRect();
        echo '     llx: ' . $rect->getLlx() . "\n";
        echo '     lly: ' . $rect->getLly() . "\n";
        echo '     urx: ' . $rect->getUrx() . "\n";
        echo '     ury: ' . $rect->getUry() . "\n";
        echo '   width: ' . $rect->getWidth() . "\n";
        echo '  height: ' . $rect->getHeight() . "\n";

        // get the field value
        $value = DictionaryHelper::resolveAttribute($widgetAnnotation->getDictionary(), 'V');
        // limited to string values for demonstration purpose
        if ($value instanceof PdfStringInterface) {
            echo '   value: ';
            echo Encoding::convertPdfString($value->getValue());
        }

        echo "</pre></br>";
    }
}