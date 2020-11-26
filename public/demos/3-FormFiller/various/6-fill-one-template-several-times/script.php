<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// let's create some example data
$firstNames = ['Peter', 'Carl', 'Dan', 'Stan', 'Roger', 'Martin', 'Paul', 'Rick', 'Chris', 'Burton'];
$lastNames = ['Walker', 'Bent', 'Stuckle', 'Willow', 'Williams', 'Müller', 'Meyer', 'Schulze', 'Cell'];
$companyNames = ['tektown Ltd.', 'camtown Ltd.', 'lenstown Ltd.', 'etown Ltd.'];

$participants = [];
for ($i = 0; $i < 30; $i++) {
    $participants[] = [
        'id' => mt_rand(100000000, 999999999),
        'Name' => $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)],
        'Company Name' => $companyNames[array_rand($companyNames)]
    ];
}

// not iterate through the data and create PDFs
foreach ($participants as $participant) {

    $writer = new SetaPDF_Core_Writer_String();
    $document = SetaPDF_Core_Document::loadByFilename(
        $assetsDirectory . '/pdfs/Name-Badge.pdf',
        $writer
    );

    $formFiller = new SetaPDF_FormFiller($document);
    $fields = $formFiller->getFields();
    $fields->get('Name')->setValue($participant['Name']);
    $fields->get('Company Name')->setValue($participant['Company Name']);

    // sadly not all PDF viewers render the barcode font correct
    $fields->get('barcode')->setValue('*' . $participant['id'] . '*');
    $fields->get('barcode text')->setValue($participant['id']);

    $document->save()->finish();

    echo '<a href="data:application/pdf;base64,' . base64_encode($writer) . '" download="' .
        $participant['id'] . '.pdf">Participant ' . $participant['id'] . '</a><br/>';

}
