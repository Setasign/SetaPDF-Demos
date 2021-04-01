<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$imageOrPdf = displaySelect('Fill with Image or PDF page?', [
    'image' => 'Image',
    'pdf' => 'PDF page'
]);

// get the main document isntance
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf',
    new SetaPDF_Core_Writer_Http('Fact-Sheet.pdf', true)
);

// get an instance of the form filler
$formFiller = new SetaPDF_FormFiller($document);

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
$xobject = SetaPDF_Core_XObject_Form::create($document, [0, 0, $width, $height]);
// get the canvas for this xobject
$canvas = $xobject->getCanvas();

if ($imageOrPdf === 'image') {
    // let's create an image xobject
    $image = SetaPDF_Core_Image::getByPath(
        $assetsDirectory . '/pdfs/tektown/Logo.png'
    )->toXObject($document);

    // or e.g. through base64 encoded image data:
    //$data = base64_decode('iVBORw0KGgoAAAANSUhEUgAABJYAAAEmCAYAAAAwZRqhAAAgAElEQVR4Xu.../w+l98Lb9eaTFwAAAABJRU5ErkJggg==');
    //$image = SetaPDF_Core_Image::get(new SetaPDF_Core_Reader_String($data))->toXObject($document);

} else {
    // let's use an existing PDF page as the logo appearance
    $logoDoc = SetaPDF_Core_Document::loadByFilename(
        $assetsDirectory . '/pdfs/tektown/Logo.pdf'
    );
    $image = $logoDoc->getCatalog()->getPages()->getPage(1)->toXObject(
        $document, SetaPDF_Core_PageBoundaries::ART_BOX
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
