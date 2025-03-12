<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\Field\AbstractField;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    require 'data/dataset-1.php',
    require 'data/dataset-2.php',
    require 'data/dataset-3.php',
];

$dataId = displaySelect('Select file:', $files, true, 'displayValue');
$data = $files[$dataId];

$document = Document::loadByFilename(
    $data['file'],
    new HttpWriter('filled.pdf', true)
);

$formFiller = new FormFiller($document);
$fields = $formFiller->getFields();

/** @var AbstractField $field */
foreach ($fields as $field) {
    $fieldName = $field->getQualifiedName();
    if (!isset($data['values'][$fieldName])) {
        continue;
    }

    $field->setValue($data['values'][$fieldName]);
}

$document->save()->finish();
