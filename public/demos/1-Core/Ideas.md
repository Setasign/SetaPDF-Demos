## Actions:
- [x] ~~Add JavaScript~~
- [x] ~~Get JavaScript~~
- [x] ~~Delete JavaScript~~
- [ ] Add an action that jumps to a specifc page and zoom when the document is opened.

## Analyze:
- [x] ~~Check for Digital Signatures~~
- [x] ~~Check for Text~~
- [x] ~~Check for Transparency~~
- [x] ~~Get Color Spaces~~
- [x] ~~Get Fonts~~
- [x] ~~Get Image Sizes and Resolution~~
- [x] ~~Check for Encryption~~
- [x] ~~Check for Collection~~
- [ ] Find unused Layers/Optional Content Groups
- [x] ~~Extract Metadata and check for PDF/A information~~

## Annotations:
- [ ] Get Comments (https://www.setasign.com/products/setapdf-core/demos/extract-comments/)
- [x] ~~Get Link Annotations~~
- [x] ~~Replace Link Targets~~
- [x] ~~Get Form Fields Information~~
- [x] ~~Flatten Annotations~~
- [x] ~~Add Link~~
- [x] ~~Add Push-Button~~
- [ ] Add Stamp Annotation with indiviudal appearance
- [ ] Add Text Field
    (Show handling of rotated pages, too)
- [ ] Show creation of annotations on rotated/shifted pages.

    Choose a file (different variations of rotations and shifted origins)
    Render the file as an image. Click on the image to create an annotation on the given 
    point and download the PDF.
    
    // compare with: X:\default\html\customers\NextSigner\test.php
    $box = $page->getCropBox();
    $rotation = $page->getRotation();
    
    $gs = new SetaPDF_Core_Canvas_GraphicState();
    switch ($rotation) {
        case -270:
        case 90:
            $gs->translate($box->getWidth(), 0);
        break;
        case -180:
        case 180:
            $gs->translate($box->getWidth(), $box->getHeight());
            break;
        case 270:
        case -90:
            $gs->translate(0, $box->getHeight());
        break;
    }
    
    $gs->rotate($box->llx, $box->lly, $rotation);
    
    $f = function($x, $y) use ($gs) {
        $v = new SetaPDF_Core_Geometry_Vector($x, $y);
        return $v->multiply($gs->getCurrentTransformationMatrix());
    };
    
    $width = 200;
    $height = 30;
    $x = 10;
    $y = 10;
    
    $ll = $f($x, $y);
    $ur = $f($x + $width, $y + $height);
    
    $field = new TextField([$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()], 'field name', $document);
    $field->getAppearanceCharacteristics(true)->setRotation($rotation);
    
    
- [ ] Show calculation of annotations on rotated pages.
    

## Document:
- [x] ~~Get Metadata~~
- [x] ~~Set Metadata~~
- [x] ~~Extract Attachments~~
    - [ ] Take annotations into account, too
- [x] ~~Multiple Pages per Sheet~~
- [x] ~~Tile a Page~~
- [x] ~~Remove Digital Signatures~~
- [x] ~~Remove Usage Rights~~
- [x] ~~Encrypt with a Password~~
- [ ] Encrypt With a Public Key
- [ ] Remove XFA information

## Images:
- [x] ~~Add Image~~
- [x] ~~Replace Images~~
- [x] ~~Image To PDF~~
- [x] ~~Image in specific Resolution~~

## Outlines:
- [x] ~~Get Outlines~~
- [x] ~~Add Entry to Existing Outline~~
- [ ] Replace a {Placeholder} in all entries

## Pages:
- [x] ~~Count~~
- [x] ~~Crop~~
- [x] ~~Fit~~
- [x] ~~Get Information~~
- [x] ~~Resize~~
- [x] ~~Rotate~~
- [x] ~~Add~~
- [x] ~~Delete~~
- [ ] Resize to a specific page format
