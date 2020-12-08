<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new SetaPDF_Core_Writer_Http('portfolio-with-folders.pdf');
$document = new SetaPDF_Core_Document($writer);
$document->getCatalog()->getPages()->create(SetaPDF_Core_PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new SetaPDF_Merger_Collection($document);

// thorugh the proxy method
$folderA = $collection->addFolder('Folder (A)');
// add more sub folders
$folderA->addFolder('Folder (AA)');
$folderA->addFolder('Folder (AB)')->addFolder('Folder (ABA)');
$folderA->addFolder('Folder (AC)')->addFolder('Folder (ACA)');

// through the root folder
$rootFolder = $collection->getRootFolder();
$folderB = $rootFolder->addFolder('Folder (B)');
// add more sub folders
$folderB->addFolder('Folder (BA)')->addFolder('Folder (BAA)');
$folderB->addFolder('Folder (BB)');
$folderB->addFolder('Folder (BC)');

// save and finish
$document->save()->finish();