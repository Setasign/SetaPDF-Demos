<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    require 'data/dataset-1.php',
    require 'data/dataset-2.php',
    require 'data/dataset-3.php',
];

$dataId = displaySelect('Select file:', $files, true, 'displayValue');
$data = $files[$dataId];

$document = SetaPDF_Core_Document::loadByFilename(
    $data['file'],
    new SetaPDF_Core_Writer_Http('filled.pdf', true)
);

$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

/** @var SetaPDF_FormFiller_Field_AbstractField $field */
foreach ($fields as $field) {
    $fieldName = $field->getQualifiedName();
    if (!isset($data['values'][$fieldName])) {
        continue;
    }

    $field->setValue($data['values'][$fieldName]);
}

$document->save()->finish();
