<?php

use setasign\SetaPDF2\Core\SecHandler;
use setasign\SetaPDF2\Core\SecHandler\Standard\Aes256;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Merger;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// simple merge process
$merger = new Merger();
$merger->addFile($assetsDirectory . '/pdfs/tektown/eBook-Invoice.pdf');
$merger->addFile($assetsDirectory . '/pdfs/tektown/Terms-and-Conditions.pdf');
$merger->merge();

$document = $merger->getDocument();

/* define a handler with an owner and a user password, allow print and
 * copy (for the user - need to be respected by the viewer application)
 * and do not encrypt metadata.
 */
$secHandler = Aes256::create(
    $document,
    'the-owner-password',
    'the-user-password',
    SecHandler::PERM_PRINT | SecHandler::PERM_COPY,
    false
);

// attach the handler to the document instance
$document->setSecHandler($secHandler);

$document->setWriter(new HttpWriter('encrypted.pdf', true));
$document->save()->finish();
