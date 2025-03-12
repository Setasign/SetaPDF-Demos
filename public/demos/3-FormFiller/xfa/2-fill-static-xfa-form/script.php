<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/xfa/CheckRequest.pdf',
    new HttpWriter('static-xfa-form.pdf', true)
);

// now get an instance of the form filler
$formFiller = new FormFiller($document);

// solution A:
$xfa = $formFiller->getXfa();
if ($xfa === false) {
    echo "No XFA data found.";
}

// pass the data packet to the setData() method:
$xfa->setData('
<form1>
    <Name>Test Person</Name>
    <Title>Dr.</Title>
    <Deptartment>Sales</Deptartment>
</form1>
');
// sync the AcroForm fields
$xfa->syncAcroFormFields();

// solution B: Same as normal AcroForm fields
$fields = $formFiller->getFields();
// will overwrite the Title
$fields['form1[0].#subform[0].Header[0].Title[0]']->setValue('Prof.');
// ...

$document->save()->finish();