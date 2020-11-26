<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the document isntance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/xfa/CheckRequest.pdf',
    new SetaPDF_Core_Writer_Http('static-xfa-form.pdf', true)
);

// now get an instance of the form filler
$formFiller = new SetaPDF_FormFiller($document);

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