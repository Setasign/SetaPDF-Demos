<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    new SetaPDF_Core_Writer_Http('filled.pdf', true)
);

$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

/** @var SetaPDF_FormFiller_Field_Text $feedbackField */
$feedbackField = $fields->get('Feedback');

// you can check for a multiline field that way:
$isMultiline = $feedbackField->isMultiline();

$feedbackField->setValue(
    "A long text, that automatically wraps if it is long enough to reach the end of the first line. But because the " .
    "field is very large, this needs some text to happen.\n" .
    "Anyhow it is also possible to force line\nbreaks\nmanually."
);

$document->save()->finish();
