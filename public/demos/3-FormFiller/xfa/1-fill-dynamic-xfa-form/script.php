<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the document isntance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/xfa/Badge.pdf',
    new SetaPDF_Core_Writer_Http('dynamic-xfa-form.pdf')
);

// now get an instance of the form filler
$formFiller = new SetaPDF_FormFiller($document);

// generate some dummy data:
$firstNames = ['Peter', 'Carl', 'Dan', 'Stan', 'Roger', 'Martin', 'Paul', 'Rick', 'Chris', 'Burton'];
$lastNames = ['Walker', 'Bent', 'Stuckle', 'Willow', 'Williams', 'Müller', 'Meyer', 'Schulze', 'Cell'];
$companyNames = ['tektown Ltd.', 'camtown Ltd.', 'lenstown Ltd.', 'etown Ltd.'];

$xml = '<badges>';

for ($i = 100; $i > 0; $i--) {
    $name = htmlspecialchars($firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)], ENT_XML1);
    $companyName = htmlspecialchars($companyNames[array_rand($companyNames)], ENT_XML1);
    $id = mt_rand(1000000, 9999999);

    $xml .= <<<XML
<badge>
    <name>$name</name>
    <company>$companyName</company>
    <barcode>$id</barcode>
</badge>
XML;
}

$xml .= '</badges>';

// get the XFA helper
$xfa = $formFiller->getXfa();
if ($xfa === false) {
    echo "No XFA data found.";
}

// pass the XML data
$xfa->setData($xml);

// save and finish
$document->save()->finish();
