<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$imageOrPdf = displaySelect('Fill with Image or PDF page?', [
    'image' => 'Image',
    'pdf' => 'PDF page'
]);

// get the main document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf',
    new HttpWriter('Fact-Sheet.pdf', true)
);

// get an instance of the form filler
$formFiller = new FormFiller($document);

// get the form fields of the document
$fields = $formFiller->getFields();

// let's fill in the contact fields
$fields['Contact 1']->setValue(
    "tektown Ltd.\n" .
    "Parker Av. 214\n" .
    "4456 Motorcity"
);

$fields['Contact 2']->setValue(
    "Phone: +01 | TEKTOWN (8358696)\n" .
    "E-Mail: post@tektown-nonexist.com\n" .
    "Web: www.tektown-nonexist.com"
);

// now prepare an appearance for the Logo field
// first of all let's get the annotation of the form field
$annotation = $fields['Logo']->getAnnotation();
// Remember the width and height for further calculations
$width = $annotation->getWidth();
$height = $annotation->getHeight();

// create a form xobject to which we are going to write the image
// this form xobject will be the resulting appearance of our form field
$xobject = Form::create($document, [0, 0, $width, $height]);
// get the canvas for this xobject
$canvas = $xobject->getCanvas();

if ($imageOrPdf === 'image') {
    // let's create an image xobject
    $image = Image::getByPath(
        $assetsDirectory . '/pdfs/tektown/Logo.png'
    )->toXObject($document);

    // or e.g. through base64 encoded image data:
    //$data = base64_decode('iVBORw0KGgoAAAANSUhEUgAABJYAAAEmCAYAAAAwZRqhAAAgAElEQVR4Xu.../w+l98Lb9eaTFwAAAABJRU5ErkJggg==');
    //$image = \setasign\SetaPDF2\Core\Image::get(new \setasign\SetaPDF2\Core\Reader\StringReader($data))->toXObject($document);

} else {
    // let's use an existing PDF page as the logo appearance
    $logoDoc = Document::loadByFilename(
        $assetsDirectory . '/pdfs/tektown/Logo.pdf'
    );
    $image = $logoDoc->getCatalog()->getPages()->getPage(1)->toXObject(
        $document, PageBoundaries::ART_BOX
    );
}

// let's define a fixed width
$imageWidth = 100;
// draw the image onto the canvas with a width of 100 and align it to the middle of the height
$image->draw($canvas, 0, $height / 2 - $image->getHeight($imageWidth) / 2, $imageWidth);

// now add the appearance to the annotation
$annotation->setAppearance($xobject);

// flatten the result
$fields->flatten();

// save and finish the document
$document->save()->finish();
