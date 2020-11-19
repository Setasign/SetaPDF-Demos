<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Subscription-tekMag.pdf',
    new SetaPDF_Core_Writer_Http('filled.pdf', true)
);

$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

/** @var SetaPDF_FormFiller_Field_Text $nameField */
$nameField = $fields->get('Name');
// or via ArrayAccess
//$nameField = $fields['Name'];

// that's how you can check for a text field type (just for demonstration here)
if ($nameField instanceof SetaPDF_FormFiller_Field_Text) {
    // set a new value
    $nameField->setValue('John Dow');
}

// we know our template, so simply fill the fields through
// the ArrayAccess implementation of the Fields instance:
$fields['Company Name']->setValue('Setasign GmbH & Co. KG');
$fields['Adress']->setValue('Max-Planck-Weg 7');
$fields['Zip Code']->setValue('38350');
$fields['City']->setValue('Helmstedt');
$fields['State']->setValue('Niedersachsen');

// for sure you can pass a variable, too
$country = 'GERMANY';
$fields['Country']->setValue($country);

$document->save()->finish();
