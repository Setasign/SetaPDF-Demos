<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/misc/FPDF-ex74.pdf',
];

$path = displayFiles($files);

// create a document instance
$document = SetaPDF_Core_Document::loadByFilename($path);

// get the documents info dictionary helper
$info = $document->getInfo();

echo '<pre>';
echo 'Following metadata were extracted from the file "' . basename($path) . "\":\n\n";

echo  'Creator: ' . $info->getCreator() . "\n"
    . 'CreationDate: ' . $info->getCreationDate() . "\n"
    . 'ModificationDate: ' . $info->getModDate(). "\n"
    . 'Author: ' . $info->getAuthor() . "\n"
    . 'Producer: ' . $info->getProducer() . "\n"
    . 'Title: ' . $info->getTitle() . "\n"
    . 'Subject: ' . $info->getSubject() . "\n"
    . 'Trapped: ' . $info->getTrapped() . "\n"
    . 'Keywords: ' . $info->getKeywords() . "\n\n";

// alternatively you can also use the getAll() method:
echo "Result of getAll():\n";
print_r($info->getAll());

// the previous method already includes custom metadata
// you can get them individually by the getAllCustomMetadata() method, too:
echo "\n\nResult of getAllCustomMetadata():\n";
print_r($info->getAllCustomMetadata());

// additionally you can access the XMP metadata package:
$metadata = $document->getCatalog()->getMetadata();
echo htmlentities($metadata);
