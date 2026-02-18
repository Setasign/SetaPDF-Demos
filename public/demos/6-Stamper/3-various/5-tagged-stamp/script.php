<?php

use setasign\SetaPDF2\Core\DataStructure\Color\Rgb;
use setasign\SetaPDF2\Demos\Stamper\Stamp\Tagged as TaggedStamp;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Stamper\Stamp\TextStamp;
use setasign\SetaPDF2\Stamper\Stamper;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';
require_once __DIR__ . '/../../../../../classes/Stamper/Stamp/Tagged.php';

// create an HTTP writer
$writer = new HttpWriter('tagged.pdf', true);
//$writer = new \setasign\SetaPDF2\Core\Writer\FileWriter('tagged.pdf');
// let's get the document
$document = \setasign\SetaPDF2\Core\Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions - Tagged.pdf',
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
$textStamp->setText(date('Y-m-d H:i:s'));
// and its color
$textStamp->setTextColor(new Rgb(240/255, 90/255, 40/255));

// create a Tagged stamp instance and pass the text stamp to it,
// we also define the parent tag found by the id "date".
$stamp = new TaggedStamp($textStamp, 'date');
// we want to add the text content into the existing tag, so let's reset the tag-name
$stamp->setTagName(null);
$stamp->setActualText($textStamp->getText());
$stamp->setTitle('Creation of this Terms and Conditions');

// add the stamp to the stamper instance
$stamper->addStamp($stamp, [
    'showOnPage' => 1,
    'position' => Stamper::POSITION_RIGHT_TOP,
    'translateX' => -40,
    'translateY' => -140,
]);

// execute the stamp process
$stamper->stamp();

// save and finish the document instance
$document->save()->finish();
