<?php

use setasign\SetaPDF2\Demos\FontLoader;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Text\RichTextBlock;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\FormFiller\FormFiller;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// get the main document instance
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Fact-Sheet-form.pdf',
    new HttpWriter('Fact-Sheet.pdf', true)
);

// get an instance of the form filler
$formFiller = new FormFiller($document);

// get the form fields of the document
$fields = $formFiller->getFields();

// we only fill the "Description" field for demonstration purpose
$description = $fields->get('Description');

// access its annotation
$annotation = $description->getAnnotation();
// store the width and height for further calculations
$width = $annotation->getWidth();
$height = $annotation->getHeight();

// create a form xobject to which we are going to write the rich-text block
// this form xobject will be the resulting appearance of our form field
$xobject = Form::create($document, [0, 0, $width, $height]);
// get the canvas for this xobject
$canvas = $xobject->getCanvas();

/* Font styles are done by using different font programs for each style. To
 * benefit from font subsetting, we need to create a callback that will create
 * the right font instances for us. See FontLoader.php for details:
 */
require_once $classesDirectory . '/FontLoader.php';
$fontLoader = new FontLoader($assetsDirectory);

// create a rich-text block instance
$textBlock = new RichTextBlock($document);
$textBlock->registerFontLoader($fontLoader);
$textBlock->setTextWidth($width - 4);
$textBlock->setPadding(2);
$textBlock->setDefaultFontFamily('DejaVuSans');
$textBlock->setDefaultFontSize(10);
$textBlock->setAlign(Text::ALIGN_JUSTIFY);
// define and set its text
$text = <<<HTML
    <span style="font-size:200%;line-height: 1">L</span>orem ipsum dolor sit amet, consetetur sadipscing elitr 
    m<sup>2</sup>, sed diam nonumy eirmod tempor invidunt ut labore et dolore <b>magna</b> aliquyam erat, sed diam 
    voluptua. At vero eos et accusam<sub>(rounded)</sub> et justo duo dolores et ea rebum. Stet <i>clita</i> kasd 
    gubergren, no sea takimata sanctus est Lorem <u>ipsum dolor sit amet</u>! Lorem ipsum dolor sit amet, consetetur 
    sadipscing elitr, sed diam nonumy eirmod tempor cam<span style="color:#e94e1c;">town</span> invidunt ut labore
    et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet 
    clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. <b>Lorem</b> ipsum dolor sit amet,
    consetetur sadipscing elitr, <u>sed diam nonumy <b>eirmod</b> tempor <i>invidunt</i></u> ut labore et dolore 
    magna aliquyam erat, sed diam voluptua. At <b><i>vero</i></b> eos et accusam et justo duo dolores et ea rebum.
    Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.<br />
    <span style="font-size:200%;line-height: 1">O</span>rem ipsum dolor sit amet, consetetur sadipscing <b>elitr</b>, 
    sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et 
    cam<span style="color:#e94e1c;">town</span> accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, 
    no sea <i>takimata</i> sanctus est Lorem <u>ipsum</u> dolor sit amet. Lorem ipsum dolor sit amet, consetetur 
     sadipscing elitr, sed diam nonumy <b>eirmod</b> tempor invidunt ut labore.
HTML;
$textBlock->setText($text);

// draw it onto the canvas
$textBlock->draw($canvas, 0, $height - $textBlock->getHeight());

// set the xobject as the appearance for the form field
$annotation->setAppearance($xobject);

// flatten all form fields
$fields->flatten();

$document->save()->finish();
