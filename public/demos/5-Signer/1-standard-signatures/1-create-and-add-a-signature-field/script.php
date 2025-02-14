<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\SignatureField;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new HttpWriter('signature-fields.pdf');
$document = new Document($writer);

// create an empty document with some pages
$pages = $document->getCatalog()->getPages();
$pages->create(PageFormats::A4);
$pages->create(PageFormats::A4, PageFormats::ORIENTATION_LANDSCAPE);

// by default the signer component will add an invisible field. You can do this manually, this way:
$fieldA = SignatureField::add($document, 'MyInvisibleSignature');

// let's create a visible signature field through the field class
$fieldB = SignatureField::add(
    $document,
    'Signature',
    1,
    SignatureField::POSITION_LEFT_TOP,
    [
        'x' => 20,
        'y' => -20
    ],
    180,
    50
);

// now create one with the same name and a fixed position
$fieldC = SignatureField::add(
    $document,
    'Signature',
    1,
    20,
    $pages->getPage(1)->getHeight() - 90 /* prev. field */ - 50 /* height */,
    180,
    50
);

// you should know that the field was added but its name was updated:
//var_dump($fieldC->getQualifiedName() === 'Signature_1');

// the Signer instance itself comes with a proxy method with nearly the same method signature:

// create a signer instance
$signer = new Signer($document);

// adds a hidden field
$fieldD = $signer->addSignatureField('Signature');
// if you want to sign e.g. $fieldD later on, you need to pass its name to the signer component:
$signer->setSignatureFieldName($fieldD->getQualifiedName());

// add a visible signature field through the proxy method
$fieldE = $signer->addSignatureField(
    'Signature',
    2,
    SignatureField::POSITION_LEFT_TOP,
    [
        'x' => 20,
        'y' => -20
    ],
    180,
    50
);
//var_dump($fieldE->getQualifiedName() === 'Signature_3');

// if you want to sign e.g. $fieldE later on, you need to pass its name to the signer component:
//$signer->setSignatureFieldName($fieldE->getQualifiedName());

$document->save()->finish();
