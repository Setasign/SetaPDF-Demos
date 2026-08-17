<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\UriAction;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Annotation;
use setasign\SetaPDF2\Core\Document\Page\Annotation\LinkAnnotation;
use setasign\SetaPDF2\Core\Image\Image;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Core\XObject\Form;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$writer = new HttpWriter('result.pdf', true);
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/misc/link-annotation-placeholders.pdf',
    $writer
);

// let's prepare some kind of mapping
$signatures = [
    'NameOfPersonX' => $assetsDirectory . '/images/Handwritten-Signature.png',
    'NameOfPersonY' => $assetsDirectory . '/images/seal.png',
];

// get access to the pages object
$pages = $document->getCatalog()->getPages();

// get the first page
$pageOne = $pages->getPage(1);

// make sure that we have a clean graphic state
$pageOne->getContents()->encapsulateExistingContentInGraphicState();

// get the canvas
$canvas = $pageOne->getCanvas();

$annotations = $pageOne->getAnnotations();
/** @var LinkAnnotation[] $linkAnnotations */
$linkAnnotations = $annotations->getAll(Annotation::TYPE_LINK);
foreach ($linkAnnotations as $linkAnnotation) {
    $action = $linkAnnotation->getAction();
    if ($action instanceof UriAction) {
        // let's parse the uri/url and ensure some keys/values. The URLs in the example document look like:
        // signature://yourDomain.com#nameOfPerson
        $uri = parse_url($action->getUri());
        if (
            !is_array($uri)
            || !isset($uri['scheme'], $uri['fragment'], $signatures[$uri['fragment']])
            || $uri['scheme'] !== 'signature'
        ) {
            continue;
        }

        $imgPath = $signatures[$uri['fragment']];
        $image = Image::getByPath($imgPath)->toXObject($document);

        // let's create a new XObject to scale/fit the signature image:
        $rect = $linkAnnotation->getRect();
        $height = $rect->getHeight();
        $width = $rect->getWidth();
        $xObject = Form::create($document, [0, 0, $width, $height]);
        $xObjectCanvas = $xObject->getCanvas();

        // fit the image into the size of the annotation
        $maxWidth = $image->getWidth($height);
        $maxHeight = $image->getHeight($width);

        $x = 0;
        $y = 0;
        if ($maxHeight > $height) {
            $x += $width / 2 - $maxWidth / 2;
            $image->draw($xObjectCanvas, $x, $y, null, $height);
        } else {
            $y += $height / 2 - $maxHeight / 2;
            $image->draw($xObjectCanvas, $x, $y, $width, null);
        }

        // draw the new xObject onto the main canvas
        $xObject->draw($canvas, $rect->llx, $rect->lly);

        // ...and remove the annotation
        $annotations->remove($linkAnnotation);
    }
}

// save and finish
$document->save()->finish();
