<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's prepare some data
$fieldData = [
    'Balloons' => 'Yes',
    'How many balloons' => '10',
    'Balloon color' => ['red', 'green'],
    'Favorite Cake' => "Apple Cake with cream.\nOr something with fancy chocolate!",
    'Pets' => 'Yes',
    'Pet kind' => 'Dog',
    'Pet name' => 'Alec',
    'Arrival' => '3pm',
    'Departure' => '9pm'
];

// use the data to create the options for this demo
$options = ['all' => 'all fields'];
foreach (array_keys($fieldData) as $fieldName) {
    $options[$fieldName] = 'Field "' . $fieldName . '"';
}

$fieldToFlatten = displaySelect('Flatten:', $options);

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
    new HttpWriter('flatten.pdf', true)
);

$formFiller = new FormFiller($document);

// access the fields instance
$fields = $formFiller->getFields();

// fill the form
foreach ($fieldData as $name => $value) {
    $fields->get($name)->setValue($value);
}

// now flatten all fields
if ($fieldToFlatten === 'all') {
    $fields->flatten();

// or a specific field
} else {
    $fields->get($fieldToFlatten)->flatten();
}

$document->save()->finish();
