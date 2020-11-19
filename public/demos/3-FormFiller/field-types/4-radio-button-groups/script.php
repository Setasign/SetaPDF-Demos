<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    new SetaPDF_Core_Writer_Http('filled.pdf', true)
);

$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

/** @var SetaPDF_FormFiller_Field_ButtonGroup $corePower */
$corePower = $fields->get('Core-Power');

// that's how you can check for a radio button group (just for demonstration here)
if ($corePower instanceof SetaPDF_FormFiller_Field_ButtonGroup) {
    // simply check the correct radio button by passing its export value to the group
    $corePower->setValue('2 x 2,8 Ghz');
}

/** @var SetaPDF_FormFiller_Field_ButtonGroup $corePower */
$ram = $fields->get('RAM');
// it is also possible to check the desired button by interacting with its instance directly:
$ramButtons = $ram->getButtons();
$ramButtons[3]->check(); // 8192MB
// or by passing a button instance
//$ram->setValue($ramButtons[1]); // 2048MB

$document->save()->finish();
