<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$writer = new SetaPDF_Core_Writer_Http('filled.pdf', true);
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/etown/Terms-and-Conditions.pdf',
    $writer
);

$formFiller = new SetaPDF_FormFiller($document);

// access the fields instance
$fields = $formFiller->getFields();

// this file has two fields with the name "Date"
// a duplicated field name is suffixed with #N
$fieldA = $fields->get('Date');
$fieldB = $fields->get('Date#1');

// you can access their original name through the getOriginalQualifiedName() method:
// $fieldA->getOriginalQualifiedName() === 'Date'
// $fieldB->getOriginalQualifiedName() === 'Date'

// you can also check for other same named/related fields that way:
// $relatedFields = $fields->getRelatedFields($fieldA);
// $relatedFields['Date#1'] === $fieldB
// $relatedFieldNames = $fields->getRelatedFieldNames('Date');
// $relatedFieldNames === ['Date#1']

// let's prepare a value
$value = $fieldA->getValue();
$value = str_replace(
    ['XXX', 'MM', 'YYYY'],
    [mt_rand(123, 999), date('m'), date('Y')],
    $value
);

// pass it for demonstration to $fieldB
$fieldB->setValue($value);

// both fields share the same value now
// $fieldA->getValue() === $value;

// now we flatten both fields manually to the pages content stream
// NOTICE: The flatten() call is NOT forwarded to same named fields!
//         Same for appearance related things like an individual font!
$fieldA->flatten();
$fieldB->flatten();

$document->save()->finish();
