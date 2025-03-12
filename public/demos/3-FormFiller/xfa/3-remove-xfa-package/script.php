<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/xfa/CheckRequest.pdf',
    new HttpWriter('normal-acro-form.pdf', true)
);

// now get an instance of the form filler
$formFiller = new FormFiller($document);

// get the XFA helper
$xfa = $formFiller->getXfa();
if ($xfa) {
    // if this is not a dynamic XFA form
    if (!$xfa->isDynamic()) {
        // remove the XFA package
        $document->getCatalog()->getAcroForm()->removeXfaInformation();
    } else {
        throw new Exception(
            'Removing the XFA package from a dynamic XFA form will result in a single PDF page showing only a ' .
            'compatibility error or loading message.'
        );
    }
}

// save the new document
$document->save()->finish();
