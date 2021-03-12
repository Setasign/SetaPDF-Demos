<?php

// load and register the autoload function
use com\setasign\SetaPDF\Demos\Stamper\Stamp\Tagged;

require_once __DIR__ . '/../../../../../bootstrap.php';
require_once __DIR__ . '/../../../../../classes/Stamper/Stamp/Tagged.php';

// create a HTTP writer
$writer = new SetaPDF_Core_Writer_Http('tagged.pdf', true);
//$writer = new SetaPDF_Core_Writer_File('tagged.pdf');
// let's get the document
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $writer
);

// create a stamper instance
$stamper = new SetaPDF_Stamper($document);

//--- Create a text stamp and wrap it in a Tagged stamp instance ---//

// create a font instance which is needed for the text stamp instance
$font = new SetaPDF_Core_Font_TrueType_Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$textStamp = new SetaPDF_Stamper_Stamp_Text($font, 10);
// set a text
$textStamp->setText('Personalized for John Dow (jon.dow@example.com)');

// create a Tagged stamp instance and pass the text stamp to it
$stamp = new Tagged($textStamp);
$stamp->setActualText($textStamp->getText());
$stamp->setTitle('Personalization information of user');

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'position' => SetaPDF_Stamper::POSITION_CENTER_TOP,
    'translateX' => 2,
    'translateY' => -2
]);


//--- Create an image stamp and wrap it in a Tagged stamp instance ---//

// get an image instance
$image = SetaPDF_Core_Image::getByPath($assetsDirectory . '/pdfs/tektown/Logo.png');
// initiate the image stamp
$imageStamp = new SetaPDF_Stamper_Stamp_Image($image);
// set height (and width until no setWidth is set the ratio will retain)
$imageStamp->setHeight(23);

// create a Tagged stamp instance and pass the image stamp to it
$stamp = new Tagged($imageStamp);
$stamp->setTagName('Figure');
$stamp->setAlternateText('Logo of "tektown"');
$stamp->setTitle('tektown');

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'showOnPage' => '2-21',
    'position' => SetaPDF_Stamper::POSITION_CENTER_BOTTOM,
    'translateY' => 10
]);


// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
