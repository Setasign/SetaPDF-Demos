## Actions:
- ~~Add JavaScript~~
- ~~Get JavaScript~~
- ~~Delete JavaScript~~

## Analyze:
- ~~Check for Digital Signatures~~
- ~~Check for Text~~
- ~~Check for Transparency~~
- ~~Get Color Spaces~~
- ~~Get Fonts~~
- ~~Get Image Sizes and Resolution~~
- ~~Check for Encryption~~
- ~~Check for Collection~~

## Annotations:
- Get Comments (https://www.setasign.com/products/setapdf-core/demos/extract-comments/)
- ~~Get Link Annotations~~
- ~~Replace Link Targets~~
- ~~Get Form Fields Information~~
- ~~Flatten Annotations~~
- ~~Add Link~~
- ~~Add Push-Button~~
- Add Text Field
    (Show handling of rotated pages, too)
- Show creation of annotations on rotated/shifted pages.
    
    
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
    
    
- Show calculation of annotations on rotated pages.
    

## Document:
- ~~Get Metadata~~
- ~~Set Metadata~~
- ~~Extract Attachments~~
    - Take annotations into account, too
- ~~Multiple Pages per Sheet~~
- ~~Tile a Page~~
- ~~Remove Digital Signatures~~
- ~~Remove Usage Rights~~

## Images:
- ~~Replace Images~~
- ~~Image To PDF~~
- ~~Image in specific Resolution~~

## Outlines:
- ~~Get Outlines~~
- Add Entry to Extisting Outline (TBD)
- Replace a {Placeholder} in all entries

## Pages:
- Count
- Crop
- Fit
- Get Data
- Resize
- Rotate
- Add
- Delete

