<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// simple merge process
$merger = new SetaPDF_Merger();
$merger->addFile($assetsDirectory . '/pdfs/tektown/eBook-Invoice.pdf');
$merger->addFile($assetsDirectory . '/pdfs/tektown/Terms-and-Conditions.pdf');
$merger->merge();

$document = $merger->getDocument();

/* define a handler with an owner and a user password, allow print and
 * copy (for the user - need to be respected by the viewer application)
 * and do not encrypt metadata.
 */
$secHandler = SetaPDF_Core_SecHandler_Standard_Aes256::factory(
    $document,
    'the-owner-password',
    'the-user-password',
    SetaPDF_Core_SecHandler::PERM_PRINT | SetaPDF_Core_SecHandler::PERM_COPY,
    false
);

// attach the handler to the document instance
$document->setSecHandler($secHandler);

$document->setWriter(new SetaPDF_Core_Writer_Http('encrypted.pdf', true));
$document->save()->finish();
