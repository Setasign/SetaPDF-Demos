<?php

use setasign\SetaPDF2\Demos\FontLoader;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\RichTextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$text = <<<HTML
An <span style="color:#ff0000">example</span> text with <span style="font-size:200%;line-height: 0.9">some</span> more content<br/>
and <u>line-breaks</u> to be able to <i style="font-size: 14pt">align</i><br/>
the text at <b>all</b>.
HTML;

// we create a blank document to show the behavior
$writer = new HttpWriter('stamped.pdf', true);
$document = new Document($writer);

// let's create 3 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(PageFormats::A4);
$pages->create(PageFormats::A4);
$pages->create(PageFormats::A4);

// create a stamper instance
$stamper = new Stamper($document);

require_once $classesDirectory . '/FontLoader.php';
$fontLoader = new FontLoader($assetsDirectory);

// create a stamp instance left aligned
$stampLeft = new RichTextStamp($document, $fontLoader);
$stampLeft->setDefaultFontFamily('DejaVuSans');
$stampLeft->setText($text);
$stampLeft->setAlign(Text::ALIGN_LEFT);
$stamper->addStamp($stampLeft);

// create a stamp instance centered
$stampCenter = new RichTextStamp($document, $fontLoader);
$stampCenter->setDefaultFontFamily('DejaVuSans');
$stampCenter->setText($text);
$stampCenter->setAlign(Text::ALIGN_CENTER);
$stamper->addStamp($stampCenter, [
    'position' => Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => 140
]);

// create a stamp instance justified
$stampCenter = new RichTextStamp($document, $fontLoader);
$stampCenter->setDefaultFontFamily('DejaVuSans');
$stampCenter->setText($text);
$stampCenter->setAlign(Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampCenter, [
    'position' => Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => -140
]);

// create a stamp instance right aligned
$stampRight = new RichTextStamp($document, $fontLoader);
$stampRight->setDefaultFontFamily('DejaVuSans');
$stampRight->setText($text);
$stampRight->setAlign(Text::ALIGN_RIGHT);
$stamper->addStamp($stampRight, Stamper::POSITION_RIGHT_BOTTOM);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
