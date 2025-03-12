<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\PageFormats;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Merger\Collection;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// create a document as the cover sheet
$writer = new HttpWriter('portfolio-with-folders.pdf');
$document = new Document($writer);
$document->getCatalog()->getPages()->create(PageFormats::A4);
// we leave it empty for demonstration purpose...

// create a collection instance
$collection = new Collection($document);

// through the proxy method
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