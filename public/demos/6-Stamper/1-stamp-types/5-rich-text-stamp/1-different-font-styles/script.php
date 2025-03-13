<?php

use setasign\SetaPDF2\Demos\FontLoader;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\RichTextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

// create a writer
$writer = new HttpWriter('styled.pdf', true);
// get a document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf',
    $writer
);

/* Font styles are done by using different font programs for each style. To
 * benefit from font subsetting, we need to create a callback that will create
 * the right font instances for us. See FontLoader.php for details:
 */
require_once $classesDirectory . '/FontLoader.php';
$fontLoader = new FontLoader($assetsDirectory);

// now simply create a stamp instance
$stamp = new RichTextStamp($document, $fontLoader);
$stamp->setDefaultFontFamily('DejaVuSans');
$stamp->setDefaultFontSize(10);
// pass an HTML like text to format the output
$stamp->setText(<<<HTML
    This document is licensed to <b><u>test@example.com</u></b> and was created on <i>www.setasign.com</i>.
HTML
);

// create a stamper instance
$stamper = new Stamper($document);
// pass the stamp instance
$stamper->addStamp($stamp, [
    'position' => Stamper::POSITION_CENTER_TOP,
    'translateY' => -5
]);

// stamp the document
$stamper->stamp();

// save and send it to the client
$document->save()->finish();
