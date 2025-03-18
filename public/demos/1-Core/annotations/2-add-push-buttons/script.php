<?php

use setasign\SetaPDF2\Demos\Annotation\Widget\PushbuttonAnnotation;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\JavaScriptAction;
use setasign\SetaPDF2\Core\Document\Action\SubmitFormAction;
use setasign\SetaPDF2\Core\Document\Page\Annotation\BorderStyle;
use setasign\SetaPDF2\Core\Font\Standard\Courier;
use setasign\SetaPDF2\Core\Font\Standard\Helvetica;
use setasign\SetaPDF2\Core\Text\TextBlock;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// if we have a post request, just dump the data
if (count($_POST) > 0) {
    $writer = new HttpWriter();
    $document = new Document($writer);
    $canvas = $document->getCatalog()->getPages()->create('a4')->getCanvas();
    $text = new TextBlock(Courier::create($document), 12);
    $text->setText(print_r($_POST, true));
    $text->draw($canvas, 0, $canvas->getHeight() - $text->getHeight());
    $document->save()->finish();
    die();
}

// if the demo is executed show a download link
if (!isset($_GET['dl'])) {
    echo '<a href="?dl=1">download</a> PDF and open in a viewer that supports PDF forms.';
    die();
}    

// require the pushbutton class
require_once('../../../../../classes/Annotation/Widget/PushbuttonAnnotation.php');

//$pdfFile = $assetsDirectory . '/pdfs/tektown/Order-Form.pdf';
$pdfFile = $assetsDirectory . '/pdfs/tektown/Subscription-tekMag.pdf';

$writer = new HttpWriter('push-buttons.pdf', false);
$document = Document::loadByFilename($pdfFile, $writer);

// let's get the page to which we want to add the button to
$pages = $document->getCatalog()->getPages();
$page = $pages->getPage(1);

$width = 100;
$height = 20;
// right top
$x = $page->getCropBox()->getUrx() - $width - 5;
$y = $page->getCropBox()->getUrY() - $height - 5;

// Create a pushbutton instance
$pb = new PushbuttonAnnotation([$x, $y, $x + $width, $y + $height], 'submit btn', $document);
$pb->setCaption('Submit');
$pb->setFontSize(12);
$pb->setTextColor([0]);
$font = Helvetica::create($document);
$pb->setFont($font);

// Define the border and style
$pb->getBorderStyle()
    ->setWidth(1)
    ->setStyle(BorderStyle::BEVELED);

// Set some appearance characteristics
$pb->getAppearanceCharacteristics(true)
    ->setBorderColor([.6])
    ->setBackgroundColor([.9]);

// Create a SubmitForm action
$target = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
$action = new SubmitFormAction($target);
$action->setFlags(
    SubmitFormAction::FLAG_EXPORT_FORMAT | /* HTTP POST */
    SubmitFormAction::FLAG_INCLUDE_NO_VALUE_FIELDS /* Send also empty fields */
);
// Attach the action to the button
$pb->setAction($action);

// Let's add the button to the pages annotation and the AcroForm array
$acroForm = $document->getCatalog()->getAcroForm();
$fields = $acroForm->getFieldsArray(true);
$annotations = $page->getAnnotations();

$fields->push($annotations->add($pb));

// Add a snd button which fills out the form with dummy values

// left top
$x = $page->getCropBox()->getLlx() + 5;
$y = $page->getCropBox()->getUrY() - $height - 5;

$pb = new PushbuttonAnnotation([$x, $y, $x + $width, $y + $height], 'random data btn', $document);
$pb->setCaption('Set values');
$pb->setFontSize(12);
$pb->setTextColor([0]);
$pb->setFont($font);

// Define the border and style
$pb->getBorderStyle()
    ->setWidth(1)
    ->setStyle(BorderStyle::BEVELED);

// Set some appearance characteristics
$pb->getAppearanceCharacteristics(true)
    ->setBorderColor([.6])
    ->setBackgroundColor([.9]);

// create a JavaScript that fills the fields with random data
$javaScript = <<<JS
var nFields = this.numFields;
var t = app.thermometer;
t.duration = nFields;
t.begin();
var name, field;
for (var i = 0; i < nFields; i++) {
    name = this.getNthFieldName(i);
    field = this.getField(name);
    switch (field.type) {
        case "text":
            field.value = name + " " + Math.floor(Math.random() * 10);
            break;
        case "checkbox":
            field.checkThisBox(0);
            break;
        case "radiobutton":
            var values = field.exportValues;
            field.value = values[Math.floor(Math.random() * (values.length - 1))];
            break;
    }

    t.value = i;
}
t.end();
JS;

// create the action
$action = new JavaScriptAction($javaScript);
// add it to the button
$pb->setAction($action);

// add the button to the annotations array and fields array
$fields->push($annotations->add($pb));

// send the document to the client
$document->save()->finish();
