<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf'
];

displayFiles($files);

// create a writer instance
$writer = new SetaPDF_Core_Writer_Http('updated-metadata.pdf', true);
// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($_GET['f'], $writer);

// get the info helper object
$info = $document->getInfo();

// we want to update the XMP metadata package automatically
$info->setSyncMetadata();

// set some info properties
$info->setTitle('Changed by SetaPDF');
$info->setSubject('Demo / Testing');
$info->setAuthor('www.setasign.com');
$info->setProducer('SetaPDF-Producer');
$info->setCreator('SetaPDF-Creator');
$info->setKeywords('KeywordA, KeywordB, KeywordC, KeywordD, KeywordE');

// set custom metadata
$info->setCustomMetadata('Data1', 'Document-Id: 1234');
$info->setCustomMetadata('Valid-Until', '2024-05-12');

// update the modification date
$info->setModDate(new DateTime());

// sync XMP metadata package
$info->syncMetadata();

// output and finish the document
$document->save()->finish();
