<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../../bootstrap.php';

$text = <<<HTML
An <span style="color:#ff0000">example</span> text with <span style="font-size:200%;line-height: 0.9">some</span> more content<br/>
and <u>line-breaks</u> to be able to <i style="font-size: 14pt">align</i><br/>
the text at <b>all</b>.
HTML;

// we create a blank document to show the behavior
$writer = new SetaPDF_Core_Writer_Http('stamped.pdf', true);
$document = new SetaPDF_Core_Document($writer);

// let's create 3 pages for demonstration purpose
$pages = $document->getCatalog()->getPages();
$pages->create(SetaPDF_Core_PageFormats::A4);
$pages->create(SetaPDF_Core_PageFormats::A4);
$pages->create(SetaPDF_Core_PageFormats::A4);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

$fontLoader = require '../FontLoader.php';

// create a stamp instance left aligned
$stampLeft = new SetaPDF_Stamper_Stamp_RichText($document, $fontLoader);
$stampLeft->setDefaultFontFamily('DejaVuSans');
$stampLeft->setText($text);
$stampLeft->setAlign(SetaPDF_Core_Text::ALIGN_LEFT);
$stamper->addStamp($stampLeft);

// create a stamp instance centered
$stampCenter = new SetaPDF_Stamper_Stamp_RichText($document, $fontLoader);
$stampCenter->setDefaultFontFamily('DejaVuSans');
$stampCenter->setText($text);
$stampCenter->setAlign(SetaPDF_Core_Text::ALIGN_CENTER);
$stamper->addStamp($stampCenter, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => 140
]);

// create a stamp instance justified
$stampCenter = new SetaPDF_Stamper_Stamp_RichText($document, $fontLoader);
$stampCenter->setDefaultFontFamily('DejaVuSans');
$stampCenter->setText($text);
$stampCenter->setAlign(SetaPDF_Core_Text::ALIGN_JUSTIFY);
$stamper->addStamp($stampCenter, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_MIDDLE,
    'translateY' => -140
]);

// create a stamp instance right aligned
$stampRight = new SetaPDF_Stamper_Stamp_RichText($document, $fontLoader);
$stampRight->setDefaultFontFamily('DejaVuSans');
$stampRight->setText($text);
$stampRight->setAlign(SetaPDF_Core_Text::ALIGN_RIGHT);
$stamper->addStamp($stampRight, SetaPDF_Stamper::POSITION_RIGHT_BOTTOM);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
