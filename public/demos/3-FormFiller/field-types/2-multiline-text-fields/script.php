<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\Field\TextField;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    new HttpWriter('filled.pdf', true)
);

$formFiller = new FormFiller($document);
$fields = $formFiller->getFields();

/** @var TextField $feedbackField */
$feedbackField = $fields->get('Feedback');

// you can check for a multiline field that way:
$isMultiline = $feedbackField->isMultiline();

$feedbackField->setValue(
    "A long text, that automatically wraps if it is long enough to reach the end of the first line. But because the "
    . "field is very large, this needs some text to happen.\n"
    . "Anyhow it is also possible to force line\nbreaks\nmanually."
);

$document->save()->finish();
