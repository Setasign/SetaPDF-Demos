<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/etown/Order-Form.pdf',
    new SetaPDF_Core_Writer_Http('flatten.pdf', true)
);

$formFiller = new SetaPDF_FormFiller($document);
$fields = $formFiller->getFields();

$signature = $fields->get('Signature');
// that's how you can check for a signature field (just for demonstration here)
if ($signature instanceof SetaPDF_FormFiller_Field_Signature) {
    // this makes nearly nothing in this example, because the field is not filled
    $signature->flatten();
}

$sendButton = $fields->get('Send');
// that's how you can check for a push button field (just for demonstration here)
if ($sendButton instanceof SetaPDF_FormFiller_Field_PushButton) {
    $sendButton->flatten();
}

$document->save()->finish();
