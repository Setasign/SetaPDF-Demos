<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\Field\ComboField;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    new HttpWriter('filled.pdf', true)
);

$formFiller = new FormFiller($document);
$fields = $formFiller->getFields();

/** @var ComboField $graphicDevice1 */
$graphicDevice1 = $fields->get('Graphics Device 1');

// that's how you can check for a combo box (just for demonstration here)
if ($graphicDevice1 instanceof ComboField) {
    // set by export value
    $graphicDevice1->setValue('EAN67834654'); // Gromlin XL, 512MB
}

/** @var ComboField $graphicDevice2 */
$graphicDevice2 = $fields->get('Graphics Device 2');
// set value by numeric index:
$graphicDevice2->setValue(3); // Tekerua 550EX, 512MB

/** @var ComboField $harddisk1 */
$harddisk1 = $fields->get('Harddisk 1');
if ($harddisk1->isEditable()) {
    // set individual value
    $harddisk1->setValue('2000GB');
}

/** @var ComboField $harddisk2 */
$harddisk2 = $fields->get('Harddisk 2');
// access the options
$options = $harddisk2->getOptions();
foreach ($options as $index => $option) {
    if ($option['visibleValue'] === '500GB') {
        $harddisk2->setValue($option['exportValue']);
        // or
        //$harddisk2->setValue($index);
        break;
    }
}

$document->save()->finish();
