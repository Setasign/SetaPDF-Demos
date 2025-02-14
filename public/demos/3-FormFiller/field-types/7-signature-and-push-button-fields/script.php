<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\Field\PushButtonField;
use setasign\SetaPDF2\FormFiller\Field\SignatureField;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/etown/Order-Form.pdf',
    new HttpWriter('flatten.pdf', true)
);

$formFiller = new FormFiller($document);
$fields = $formFiller->getFields();

$signature = $fields->get('Signature');
// that's how you can check for a signature field (just for demonstration here)
if ($signature instanceof SignatureField) {
    // this makes nearly nothing in this example, because the field is not filled
    $signature->flatten();
}

$sendButton = $fields->get('Send');
// that's how you can check for a push button field (just for demonstration here)
if ($sendButton instanceof PushButtonField) {
    $sendButton->flatten();
}

$document->save()->finish();
