<?php

use setasign\SetaPDF2\Demos\Annotation\Widget\TextFieldAnnotation;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\BorderStyle;
use setasign\SetaPDF2\Core\Font\Standard\Helvetica;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// require the text field class
require_once('../../../../../classes/Annotation/Widget/TextFieldAnnotation.php');

$writer = new HttpWriter('TextFields.pdf', true);
$document = new Document($writer);

// let's create a page to which we want to add the fields to
$pages = $document->getCatalog()->getPages();
$page = $pages->create(PageFormats::A4);

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
    BorderStyle::BEVELED,
    BorderStyle::DASHED,
    BorderStyle::INSET,
    BorderStyle::SOLID,
    BorderStyle::UNDERLINE
];

$borderSizes = [1, 2, 3, 4];

$fontSizes = [0, 5, 8, 10, 12, 18, 24];

$aligns = [
    Text::ALIGN_LEFT,
    Text::ALIGN_CENTER,
    Text::ALIGN_RIGHT
];

// let's define the postion of the first field
$x = $page->getCropBox()->getLlx() + 5;
$y = $page->getCropBox()->getUrY() - 5;

// we use the same font for all fields
$font = Helvetica::create($document);

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

    // Create a text field instance
    $field = new TextFieldAnnotation([$x, $y - $height - ($multiline ? 20 : 0), $x + $width, $y], 'field name ' . $i, $document);
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
