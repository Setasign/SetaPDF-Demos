<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\Field\CheckboxButtonField;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    new HttpWriter('filled.pdf', true)
);

$formFiller = new FormFiller($document);
$fields = $formFiller->getFields();

/** @var CheckboxButtonField $wlanCb */
$wlan = $fields->get('WLAN');

// that's how you can check for a checkbox (just for demonstration here)
if ($wlan instanceof CheckboxButtonField) {
    // simply check it:
    $wlan->check();
    // or uncheck it
    //$wlan->uncheck();
}

/** @var CheckboxButtonField $bluetoothCb */
$bluetooth = $fields->get('Bluetooth');
// you also can pass true/false to the setValue() method:
$bluetooth->setValue(true);
// or uncheck it
//$bluetooth->setValue(false);

/** @var CheckboxButtonField $cardReaderCb */
$cardReader = $fields->get('Card Reader');
// it is also possible to check it by passing its export value to the setValue() method:
$cardReader->setValue('Yes');
// or uncheck it
//$cardReader->setValue('Anything but its export value');

$document->save()->finish();
