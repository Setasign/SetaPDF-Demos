<?php

require_once __DIR__ . '/../../../../../../bootstrap.php';

$files = array_merge([
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
], $sessionFiles);

if (!isset($_GET['f']) || !in_array($_GET['f'], $files)) {
    foreach ($files as $path) {
        $name = basename($path);
        echo '<a href="?f=' . urlencode($path) . '">';
        echo htmlspecialchars($name, ENT_QUOTES | ENT_HTML5);
        echo '</a><br />';
    }

    echo '<br />';
    die();
}

//require_once('library/SetaPDF/Autoload.php');
// or if you use composer require_once('vendor/autoload.php');

$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);

// authenticate against security handler
//if ($document->hasSecHandler()) {
//    $document->getSecHandler()->auth($password);
//}

// Get the documents info dictionary helper
$info = $document->getInfo();

echo 'Following metadata were extracted from the file "' . $_GET['f'] . "\":\n\n";

echo  'Creator: ' . $info->getCreator() . "\n"
    . 'CreationDate: ' . $info->getCreationDate() . "\n"
    . 'ModificationDate: ' . $info->getModDate(). "\n"
    . 'Author: ' . $info->getAuthor() . "\n"
    . 'Producer: ' . $info->getProducer() . "\n"
    . 'Title: ' . $info->getTitle() . "\n"
    . 'Subject: ' . $info->getSubject() . "\n"
    . 'Trapped: ' . $info->getTrapped() . "\n"
    . 'Keywords: ' . $info->getKeywords() . "\n\n";

// alternatively you can also use getAll, the return is an array with all set information
echo 'Via getAll(): ';
print_r($info->getAll());
