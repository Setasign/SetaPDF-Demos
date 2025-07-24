<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$writer = new HttpWriter('Fact-Sheet-TDM.pdf', true);
$document = Document::loadByFilename($assetsDirectory . '/pdfs/camtown/Fact-Sheet.pdf', $writer);

$ns = 'http://www.w3.org/ns/tdmrep/';
$alias = 'tdm';

$info = $document->getInfo();

$xmp = $info->getXmp();
$xmp->xmlAliases[$ns] = $alias;

$info->updateXmp($ns, 'reservation', 1);
$info->updateXmp($ns, 'policy', 'https://www.example.com/policies/tdm-policy.json');
$info->syncMetadata();

$document->save()->finish();
