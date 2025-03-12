<?php

use setasign\SetaPDF2\Core\DataStructure\Rectangle as RectangleDataStructure;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Popup;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Square;
use setasign\SetaPDF2\Core\Font\Standard\Courier;
use setasign\SetaPDF2\Core\Geometry\Rectangle as RectangleGeometry;
use setasign\SetaPDF2\Core\PageBoundaries;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Reader\StringReader;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Text\Block;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\Writer\StringWriter;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a string writer for a temporary result
$writer = new StringWriter();
// create a document
$document = new Document($writer);

// the wanted format
$wantedFormat = [200, 100];

// get the pages helper
$pages = $document->getCatalog()->getPages();

// create a new font
$font = Courier::create($document);

// create an array with different formats
$formats = [
    [ // 1
        PageFormats::A4,
        "A4\n" . PageFormats::A4,
        0,
        70,
    ],
    [ // 2
        [100, 100, 0, 0],
        "100x100\n100,100,0,0",
        90,
        10,
    ],
    [ // 3
        [0, 0, 200, 200],
        "200x200\n0,0,200,200",
        90,
        10,
    ],
    [ // 4
        [-200, -700, 200, 700],
        "400x1400\n-200,-700,200,700",
        90,
        30,
    ],
    [ // 5
        [10, 50, 210, 550],
        "200x500\n10,50,210,550",
        90,
        20,
    ],
    [ // 6
        [-210, -550, -10, -50],
        "200x500\n-210,-550,-10,-50",
        -90,
        15,
    ],
    [ // 7
        [-10, -50, -210, -550],
        "200x500\n-10,-50,-210,-550",
        270,
        15,
    ],
    [ // 8
        [-100, 100, -200, -100],
        "100x200\n-100,100,-200,-100",
        360,
        7,
    ],
    [ // 9
        [2000, 5000, 4000, 2000],
        "2000x3000\n2000,5000,4000,2000",
        180,
        150,
    ],
    [ // 10
        [50, 10, 550, 210],
        "500x200\n50,10,550,210",
        0,
        50,
    ],
    [ // 11
        [2000, -3900, 2500, -3000],
        "500x900\n2000,-3900,2500,-3000",
        0,
        30,
    ]
];

// create the pages
foreach ($formats as $formatArray) {
    list($format, $formatName, $rotation, $fontSize) = $formatArray;

    // create a new page with the given format
    $page = $pages->create($format);

    // set the rotation
    $page->setRotation($rotation);

    // get the canvas
    $canvas = $page->getCanvas();

    // set the line width
    $canvas->path()->setLineWidth(10);

    // draw a rectangle around the page
    $canvas->draw()->rect(
        $page->getCropBox()->llx + 5,
        $page->getCropBox()->lly + 5,
        $page->getCropBox()->urx - $page->getCropBox()->llx - 10,
        $page->getCropBox()->ury - $page->getCropBox()->lly - 10
    );

    // create a new text block
    $text = new Block($font, $fontSize);

    // set the alignment
    $text->setAlign(Text::ALIGN_CENTER);

    // set the text of the text block
    $text->setText($formatName);

    // draw the text in the center
    $x = ($page->getCropBox()->getUrx() - $page->getCropBox()->getWidth() / 2)  - $text->getWidth() / 2;
    $y = ($page->getCropBox()->getUry() - $page->getCropBox()->getHeight() / 2) - $text->getHeight() / 2;
    $text->draw($canvas, $x, $y);

    // create an annotation for demonstration purpose
    $annotation = new Square(
        [$x, $y, $x + $text->getWidth(), $y + $text->getHeight()]
    );

    // set the color of the annotation
    $annotation->setColor([0, 1, .2]);

    // add the annotation to the page
    $page->getAnnotations()->add($annotation);
}

// save and finish the temporary document
$document->save()->finish();

// transfer the document into a new instance
$fittedDocument = Document::load(
    new StringReader($writer),
    new HttpWriter('fit.pdf', true)
);

// get the pages object
$pages = $fittedDocument->getCatalog()->getPages();

// get the page count
$pageCount = $pages->count();

// iterate through all pages
for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
    // get the page object
    $page = $pages->getPage($pageNumber);

    // sanitize the current page format
    $format = PageFormats::getFormat($page->getWidthAndHeight(), PageFormats::ORIENTATION_AUTO);

    // check if the format of the current page is the desired format
    if (PageFormats::is($wantedFormat, $format)) {
        continue;
    }

    // determine the orientation of the page
    $orientation = $page->getOrientation();

    // align the wanted format to the page orientation
    $wantedFormatArray = PageFormats::getFormat($wantedFormat, $orientation);

    // check if the page is rotated
    $rotated = ($page->getRotation() / 90) % 2;

    // calculate the scale factors
    $scaleX = $wantedFormatArray['width'] / $format['width'];
    $scaleY = $wantedFormatArray['height'] / $format['height'];

    // switch scale values if the page is rotated
    if ($rotated) {
        list($scaleX, $scaleY) = [$scaleY, $scaleX];
    }

    // take the smaller value as the scaling factor
    $scale = min($scaleX, $scaleY);

    // let's scale the page content
    $canvas = $page->getCanvas();
    $contents = $page->getContents();

    // we add a separate content stream before the existing stream
    $stream = $contents->prependStream(true);
    // save the graphic state
    $canvas->saveGraphicState();

    // get the old boundary
    $cropBox = $page->getBoundary();

    // calculate the scaling
    $rect = new RectangleGeometry(
        $cropBox->getLlx() * $scale,
        $cropBox->getLly() * $scale,
        $cropBox->getUrx() * $scale,
        $cropBox->getUry() * $scale
    );

    // calculate the values for centering
    $centerX = ($wantedFormatArray['width'] - (!$rotated ? $rect->getWidth() : $rect->getHeight())) / 2;
    $centerY = ($wantedFormatArray['height'] - (!$rotated ? $rect->getHeight() : $rect->getWidth())) / 2;

    // switch scale values if page is rotated
    if ($rotated) {
        list($centerX, $centerY) = [$centerY, $centerX];
    }

    // calculate the shift that is caused by the scale and add the centering value
    $translateX = $cropBox->getLlx() - $rect->getLl()->getX() + $centerX;
    $translateY = $cropBox->getLly() - $rect->getLl()->getY() + $centerY;

    // let's translate the canvas
    $canvas->translate($translateX, $translateY);

    // let's scale
    $canvas->scale($scale, $scale);

    // append a closing content stream
    $stream = $contents->pushStream(true);
    // and restore the opened graphic state
    $canvas->restoreGraphicState();

    // let's adjust the boundary boxes
    $boxes = PageBoundaries::$all;
    // reverse the boxes order to pass valid boxes to the page
    if ($scale < 1) {
        $boxes = array_reverse($boxes);
    }

    // iterate through all possible boundaries
    foreach ($boxes AS $boxName) {
        $_box = $page->getBoundary($boxName, false, true);
        if ($_box === false) {
            continue;
        }

        // create a new boundary box
        $box = RectangleDataStructure::byArray(
            [
                $_box->getLlx(),
                $_box->getLly(),
                $_box->getUrx() * $scaleX + ($_box->getLlx() - ($_box->getLlx() * $scaleX )),
                $_box->getUry() * $scaleY + ($_box->getLly() - ($_box->getLly() * $scaleY ))
            ]
        );

        // reset the boundary box
        $page->setBoundary($box, $boxName, false);
    }

    // now we adjust the coords of all annotation objects
    $annotations = $page->getAnnotations();
    $all = $annotations->getAll();
    foreach ($all AS $annotation) {
        $dict = $annotation->getDictionary();

        // the size of a popup annotation has to be handled individually
        // we just use the scale and translate values which were calculated before
        if ($annotation instanceof Popup) {
            $_rect = $annotation->getRect();

            $rect = RectangleDataStructure::byArray([
                $_rect->getLlx() * $scale + $translateX,
                $_rect->getLly() * $scale + $translateY + $_rect->getHeight(),
                $_rect->getLlx() * $scale + $translateX + $_rect->getWidth(),
                $_rect->getUry() * $scale + $translateY
            ]);
            $dict['Rect'] = $rect->getValue();
            continue;
        }

        $_rect = $annotation->getRect();
        $rect = RectangleDataStructure::byArray([
            $_rect->getLlx() * $scale + $translateX,
            $_rect->getLly() * $scale + $translateY,
            $_rect->getUrx() * $scale + $translateX,
            $_rect->getUry() * $scale + $translateY
        ]);

        $dict['Rect'] = $rect->getValue();
    }
}

// save the document
$fittedDocument->save()->finish();