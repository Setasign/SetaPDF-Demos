<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the document isntance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/forms/xfa/CheckRequest.pdf',
    new SetaPDF_Core_Writer_Http('normal-acro-form.pdf', true)
);

// now get an instance of the form filler
$formFiller = new SetaPDF_FormFiller($document);

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
