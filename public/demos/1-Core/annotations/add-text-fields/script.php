<?php

use com\setasign\SetaPDF\Demos\Annotation\Widget\TextField;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// require the text field class
require_once('../../../../../classes/Annotation/Widget/TextField.php');

$writer = new SetaPDF_Core_Writer_Http('TextFields.pdf', true);
$document = new SetaPDF_Core_Document($writer);

// let's create a page to which we want to add the fields to
$pages = $document->getCatalog()->getPages();
$page = $pages->create(SetaPDF_Core_PageFormats::A4);

// prepare some variables we need later
$acroForm = $document->getCatalog()->getAcroForm();
$fields = $acroForm->getFieldsArray(true);
$annotations = $page->getAnnotations();

// define some field properties
$width = 200;
$height = 30;

// some properties we can choose randomly
$colors = [
    [.6],
    [.9],
    [1, 0, 0],
    [0, 1, 0],
    [0, 0, 1],
    [1, 1, 0],
    [1, 1, 1],
    [1, 0, 1],
    [0, 1, 1],
    [.5, .5, .5],
    [.5, 0, .75]
];

$borderStyles = [
    SetaPDF_Core_Document_Page_Annotation_BorderStyle::BEVELED,
    SetaPDF_Core_Document_Page_Annotation_BorderStyle::DASHED,
    SetaPDF_Core_Document_Page_Annotation_BorderStyle::INSET,
    SetaPDF_Core_Document_Page_Annotation_BorderStyle::SOLID,
    SetaPDF_Core_Document_Page_Annotation_BorderStyle::UNDERLINE
];

$borderSizes = [1, 2, 3, 4];

$fontSizes = [0, 5, 8, 10, 12, 18, 24];

$aligns = [
    SetaPDF_Core_Text::ALIGN_LEFT,
    SetaPDF_Core_Text::ALIGN_CENTER,
    SetaPDF_Core_Text::ALIGN_RIGHT
];

// let's define the postion of the first field
$x = $page->getCropBox()->getLlx() + 5;
$y = $page->getCropBox()->getUrY() - 5;

// we use the same font for all fields
$font = SetaPDF_Core_Font_Standard_Helvetica::create($document);

// let's create 18 text fields with random properties
for ($i = 0; $i < 18; $i++) {
    $fontSize = $fontSizes[array_rand($fontSizes)];
    $textColor = $colors[array_rand($colors)];
    $borderWidth = $borderSizes[array_rand($borderSizes)];
    $borderStyle = $borderStyles[array_rand($borderStyles)];
    $borderColor = $colors[array_rand($colors)];
    $backgroundColor = $colors[array_rand($colors)];
    $multiline = ($i & 1) === 1;
    $align = $aligns[array_rand($aligns)];

    // Create a textfield instance
    $field = new TextField([$x, $y - $height - ($multiline ? 20 : 0), $x + $width, $y], 'field name ' . $i, $document);
    $field->setValue('A simple test which is a bit longer to show line breaking behavior (' . $i . ').');
    $field->setFontSize($fontSize);
    $field->setTextColor($textColor);
    $field->setMultiline($multiline);
    $field->setAlign($align);
    $field->setFont($font);

    // Define the border and style
    $field->getBorderStyle(true)
        ->setWidth($borderWidth)
        ->setStyle($borderStyle);

    // Set some appearance characteristics
    $field->getAppearanceCharacteristics(true)
        ->setBorderColor($borderColor)
        ->setBackgroundColor($backgroundColor);

    // Add the field to the page and main AcroForm array
    $fields->push($annotations->add($field));

    $y -= $field->getHeight() + 2;
}

// send the document to the client
$document->save()->finish();
