<?php

use setasign\SetaPDF2\Demos\Stamper\Stamp\Tagged as TaggedStamp;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\Image as ImageStamp;
use setasign\SetaPDF2\Stamper\Stamp\Text as TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';
require_once __DIR__ . '/../../../../../classes/Stamper/Stamp/Tagged.php';

// create an HTTP writer
$writer = new HttpWriter('tagged.pdf', true);
//$writer = new \setasign\SetaPDF2\Core\Writer\FileWriter('tagged.pdf');
// let's get the document
$document = \setasign\SetaPDF2\Core\Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $writer
);

// create a stamper instance
$stamper = new Stamper($document);

//--- Create a text stamp and wrap it in a Tagged stamp instance ---//

// create a font instance which is needed for the text stamp instance
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSans.ttf'
);

// create a stamp instance
$textStamp = new TextStamp($font, 10);
// set a text
$textStamp->setText('Personalized for John Dow (jon.dow@example.com)');

// create a Tagged stamp instance and pass the text stamp to it
$stamp = new TaggedStamp($textStamp);
$stamp->setActualText($textStamp->getText());
$stamp->setTitle('Personalization information of user');

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'position' => Stamper::POSITION_CENTER_TOP,
    'translateX' => 2,
    'translateY' => -2
]);


//--- Create an image stamp and wrap it in a Tagged stamp instance ---//

// get an image instance
$image = Image::getByPath($assetsDirectory . '/pdfs/tektown/Logo.png');
// initiate the image stamp
$imageStamp = new ImageStamp($image);
// set height (and width until no setWidth is set the ratio will retain)
$imageStamp->setHeight(23);

// create a Tagged stamp instance and pass the image stamp to it
$stamp = new TaggedStamp($imageStamp);
$stamp->setTagName('Figure');
$stamp->setAlternateText('Logo of "tektown"');
$stamp->setTitle('tektown');

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'showOnPage' => '2-21',
    'position' => Stamper::POSITION_CENTER_BOTTOM,
    'translateY' => 10
]);


// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
