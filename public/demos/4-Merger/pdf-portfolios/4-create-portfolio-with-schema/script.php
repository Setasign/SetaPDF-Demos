<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new \SetaPDF_Core_Writer_Http('portfolio-with-schema.pdf');
$document = new \SetaPDF_Core_Document($writer);
$document->getCatalog()->getPages()->create(\SetaPDF_Core_PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new SetaPDF_Merger_Collection($document);

// get the schema instance
$schema = $collection->getSchema();

// create a field instance manually
$filenameField = SetaPDF_Merger_Collection_Schema_Field::create(
    'Filename', // the visible field name
    SetaPDF_Merger_Collection_Schema::DATA_FILE_NAME // refer to the file name
);
$filenameField->setOrder(1);
// add it to the schema
$schema->addField('filename', $filenameField);

// let addField() do the field creation
$schema->addField(
    'description',
    'Description',
    SetaPDF_Merger_Collection_Schema::DATA_DESCRIPTION,
    2
);

// let's create an individual field
$schema->addField(
    'company',
    'Company Name',
    SetaPDF_Merger_Collection_Schema::TYPE_STRING,
    3
);

// let's create another individual field
$orderField = $schema->addField(
    'order',
    'Order',
    SetaPDF_Merger_Collection_Schema::TYPE_NUMBER,
    4
);
// but hide it
$orderField->setVisibility(false);

// set default sorting
$collection->setSort(['order' => SetaPDF_Merger_Collection::SORT_ASC]);

// for demonstration purpose, we add some files now...
$collection->addFile(
    $assetsDirectory . '/pdfs/tektown/Logo.pdf',
    'tektown-logo.pdf',
    'The logo of tektown',
    [],
    'application/pdf',
    [
        'company' => \SetaPDF_Core_Encoding::toPdfString('tektown'),
        'order'   => 3
    ]
);

$collection->addFile(
    $assetsDirectory . '/pdfs/etown/Logo.pdf',
    'etown-logo.pdf',
    'The logo of etown',
    [],
    'application/pdf',
    [
        'company' => \SetaPDF_Core_Encoding::toPdfString('etown'),
        'order'   => 2
    ]
);

$collection->addFile(
    $assetsDirectory . '/pdfs/lenstown/Logo.pdf',
    'lenstown-logo.pdf',
    'The logo of lenstown',
    [],
    'application/pdf',
    [
        'company' => \SetaPDF_Core_Encoding::toPdfString('lenstown'),
        'order'   => 4
    ]
);

// now we add a folder
$imagesFolder = $collection->addFolder(
    'Images',
    'All logos as PNG images',
    null,
    null,
    [
        'order' => 1
    ]
);

// and add some more files to the folder
$imagesFolder->addFile(
    $assetsDirectory . '/pdfs/tektown/Logo.png',
    'tektown-logo.png',
    'The logo of tektown',
    [],
    'image/png',
    [
        'company' => \SetaPDF_Core_Encoding::toPdfString('tektown'),
        'order'   => 3
    ]
);

$imagesFolder->addFile(
    $assetsDirectory . '/pdfs/etown/Logo.png',
    'etown-logo.png',
    'The logo of etown',
    [],
    'image/png',
    [
        'company' => \SetaPDF_Core_Encoding::toPdfString('etown'),
        'order'   => 2
    ]
);

$imagesFolder->addFile(
    $assetsDirectory . '/pdfs/lenstown/Logo.png',
    'lenstown-logo.png',
    'The logo of lenstown',
    [],
    'image/png',
    [
        'company' => \SetaPDF_Core_Encoding::toPdfString('lenstown'),
        'order'   => 1
    ]
);

// save and finish
$document->save()->finish();
