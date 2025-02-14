<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\Field\ListField;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
    new HttpWriter('filled.pdf', true)
);

$formFiller = new FormFiller($document);
$fields = $formFiller->getFields();

/** @var ListField $balloonColor */
$balloonColor = $fields->get('Balloon color');

// that's how you can check for a list field (just for demonstration here)
if ($balloonColor instanceof ListField) {
    // set by export value
    $balloonColor->setValue('black');
}

// set by index
$balloonColor->setValue(3); // Yellow

if ($balloonColor->isMultiSelect()) {
    // if it is a multiselect you can pass an array of indexes or export values
    $balloonColor->setValue([1, 'yellow', 4]); // blue, yellow, orange
}

// get the available options
$options = $balloonColor->getOptions();
$values = [];
foreach ($options as $index => $option) {
    if ($option['exportValue'] === 'yellow') {
        $values[] = $option['exportValue'];
    } elseif ($option['visibleValue'] === 'Blue') {
        $values[] = $index; // or $option['exportValue']
    }
}

// and set the values
$balloonColor->setValue($values); // Blue, Yellow

$document->save()->finish();
