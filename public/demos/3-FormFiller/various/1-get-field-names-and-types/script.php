<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/lenstown/Order-Form.pdf',
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
    $assetsDirectory . '/pdfs/etown/Terms-and-Conditions.pdf',
];

$path = displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($path);

$formFiller = new SetaPDF_FormFiller($document);

// access the fields instance
$fields = $formFiller->getFields();
// get all field names
$names = $fields->getNames();

foreach ($names as $name) {
    echo 'Field: <b>' . htmlspecialchars($name) . '</b><br />';

    // to get the field type, you need to access/create a field instance
    $field = $fields->get($name);
    echo 'Type: ' . get_class($field) . '<br/>';

    echo '<br />';
}
