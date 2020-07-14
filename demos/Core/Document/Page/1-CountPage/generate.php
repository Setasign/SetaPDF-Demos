<?php

//require_once('library/SetaPDF/Autoload.php');
// or if you use composer require_once('vendor/autoload.php');

$document = SetaPDF_Core_Document::loadByFilename($file);

$pages = $document->getCatalog()->getPages();
$pageCount = $pages->count();
// or
// $pageCount = count($pages);

echo 'The document "' . basename($file) . '" has ' .
    ($pageCount == 1 ? '1 page' : $pageCount . ' pages');
