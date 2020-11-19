<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    new SetaPDF_Core_Writer_Http('filled.pdf', true)
);

$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

/** @var SetaPDF_FormFiller_Field_Button $wlanCb */
$wlan = $fields->get('WLAN');

// that's how you can check for a check box (just for demonstration here)
if ($wlan instanceof SetaPDF_FormFiller_Field_Button) {
    // simply check it:
    $wlan->check();
    // or uncheck it
    //$wlan->uncheck();
}

/** @var SetaPDF_FormFiller_Field_Button $bluetoothCb */
$bluetooth = $fields->get('Bluetooth');
// you also can pass true/false to the setValue() method:
$bluetooth->setValue(true);
// or uncheck it
//$bluetooth->setValue(false);

/** @var SetaPDF_FormFiller_Field_Button $cardReaderCb */
$cardReader = $fields->get('Card Reader');
// it is also possible to check it by passing its export value to the setValue() method:
$cardReader->setValue('Yes');
// or uncheck it
//$cardReader->setValue('Anything but its export value');

$document->save()->finish();
