<?php

use setasign\SetaPDF2\Core\ColorSpace\Separation;
use setasign\SetaPDF2\Core\DataStructure\Color\Special;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Font\TrueType\Subset;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the main document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Order-Form-without-Signaturefield.pdf',
    new HttpWriter('OwnFontAndTextColor.pdf', true)
);

// now get an instance of the form filler
$formFiller = new FormFiller($document);

// get the form fields of the document
$fields = $formFiller->getFields();

// create a spot color/space
$colorSpace = Separation::createSpotColor($document, 'HKS 27 N', .3, 1, 0, 0);
$color = new Special(1);

// let's create a font instance DejaVu Sans Condensed as a TrueType font subset
$font = new Subset(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSansCondensed-Oblique.ttf'
);

// Or as a CID font with true type outlines which allows you to use more than 255 characters
/*
$font = new \setasign\SetaPDF2\Core\Font\Type0\Subset(
    $document,
    $assetsDirectory . '/fonts/dDejaVu/ttf/DejaVuSansCondensed-Oblique.ttf'
);*/

/* NOT RECOMMENDED: alternatively you can create an instance with a font which will not be embedded.*/
/* $font = \setasign\SetaPDF2\Core\Font\TrueType::create(
    $document,
    $assetsDirectory . '/fonts/DejaVu/ttf/DejaVuSansCondensed-Oblique.ttf',
    \setasign\SetaPDF2\Core\Encoding::WIN_ANSI,
    'auto',
    false // <-- don't embedded
);*/

$field = $fields->get('Name');
$field->setAppearanceTextColorSpace($colorSpace);
$field->setAppearanceTextColor($color);
$field->setAppearanceFont($font);
$field->setValue('Mr. Úmśęnłasdí'); // Let's play with some unicode chars
$field->flatten();

$field = $fields->get('Company Name');
$field->setAppearanceTextColorSpace($colorSpace);
$field->setAppearanceTextColor($color);
$field->setAppearanceFont($font);
$field->setValue('Μεγάλη εταιρεία');
$field->flatten();

$field = $fields->get('Adress');
$field->setAppearanceTextColorSpace($colorSpace);
$field->setAppearanceTextColor($color);
$field->setAppearanceFont($font);
$field->setValue("Φανταστικό δρόμο ③");
$field->flatten();

// finish the document
$document->save()->finish();
