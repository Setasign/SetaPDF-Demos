<?php

use setasign\SetaPDF2\Core\Canvas\GraphicState;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\FreeTextAnnotation;
use setasign\SetaPDF2\Core\Font\Standard\Helvetica;
use setasign\SetaPDF2\Core\Geometry\Vector;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Text\Block;
use setasign\SetaPDF2\Core\Type\PdfString;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\XObject\Form;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// let's define some properties first
$x = 10;
$yTop = 10; // we take the upper left as the origin

$borderWidth = 1;

$borderColor = '#FF0000';
$fillColor = '#00FF00';
$textColor = '#0000FF';

$text = "Received: " . date('Y-m-d H:i:s');
$align = Text::ALIGN_LEFT;

// create a document instance by loading an existing PDF
$writer = new HttpWriter('signed+free-text-annotation.pdf', true);
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-signed.pdf',
    $writer
);

// we will need a font instance
$font = Helvetica::create($document);
$fontSize = 12;

// now we create a text block first to know the final size:
$box = new Block($font, $fontSize);
$box->setTextColor($textColor);
$box->setBorderWidth($borderWidth);
$box->setBorderColor($borderColor);
$box->setBackgroundColor($fillColor);
$box->setAlign($align);
$box->setText($text);
$box->setPadding(2);

$width = $box->getWidth();
$height = $box->getHeight();

// now draw the text block onto a canvas (we add the $borderWidth to show the complete border)
$appearance = Form::create($document, [0, 0, $width + $borderWidth, $height + $borderWidth]);
$box->draw($appearance->getCanvas(), $borderWidth / 2, $borderWidth / 2);

// now we need a page and calculate the correct coordinates for our annotation
$page = $document->getCatalog()->getPages()->getPage(1);
// we need its rotation
$rotation = $page->getRotation();
// ...and page boundary box
$box = $page->getBoundary();

// with this information we create a graphic state
$pageGs = new GraphicState();
switch ($rotation) {
    case 90:
        $pageGs->translate($box->getWidth(), 0);
        break;
    case 180:
        $pageGs->translate($box->getWidth(), $box->getHeight());
        break;
    case 270:
        $pageGs->translate(0, $box->getHeight());
        break;
}

$pageGs->rotate($box->llx, $box->lly, $rotation);
$pageGs->translate($box->llx, $box->lly);

// ...and a helper function to translate coordinates into vectors by using the page graphic state
$f = static function($x, $y) use ($pageGs) {
    $v = new Vector($x, $y);
    return $v->multiply($pageGs->getCurrentTransformationMatrix());
};

// calculate the ordinate
$y = $page->getHeight() - $height - $yTop;

$ll = $f($x, $y);
$ur = $f($x + $width + $borderWidth, $y + $height + $borderWidth);

// now we create the annotation object:
$annotation = new FreeTextAnnotation(
    [$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()],
    'Helv',
    $fontSize,
    $borderColor
);
$annotation->getBorderStyle()->setWidth($borderWidth);
$annotation->setColor($fillColor);
$annotation->setTextLabel("John Dow"); // Used as Author in a Reader application
$annotation->setContents($text);
$annotation->setName(uniqid('', true));
$annotation->setModificationDate(new DateTime());
$annotation->setAppearance($appearance);

// now we need to add some things regarding "variable text" that are required by e.g. Acrobat (if you want to add
// e.g. a digital signature directly after adding a free-text annotation)
$dict = $annotation->getDictionary();
$dict->offsetSet(
    'DS',
    new PdfString('font: Helvetica, sans-serif ' . sprintf('%.2F', $fontSize) . 'pt;color: ' . $textColor)
);
switch ($align) {
    case Text::ALIGN_CENTER:
        $align = 'center';
        break;
    case Text::ALIGN_RIGHT:
        $align = 'right';
        break;
    case Text::ALIGN_JUSTIFY:
        $align = 'justify';
        break;
    default:
        $align = 'left';
}

$dict->offsetSet('RC', new PdfString(
    '<?xml version="1.0"?><body xmlns="http://www.w3.org/1999/xhtml" xmlns:xfa="http://www.xfa.org/schema/xfa-data/1.0/" ' .
    'xfa:APIVersion="Acrobat:11.0.23" xfa:spec="2.0.2"  style="font-size:' . $fontSize . 'pt;text-align:' . $align .
    ';color:' . $textColor . ';font-weight:normal;font-style:normal;font-family:Helvetica,sans-serif;font-stretch:normal">' .
    '<p dir="ltr"><span style="font-family:Helvetica">' . htmlentities($annotation->getContents(), ENT_XML1) . '</span></p></body>'
));

// lastly add the annotation to the page
$page->getAnnotations()->add($annotation);

$document->save()->finish();
