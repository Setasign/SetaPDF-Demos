<?php

require_once __DIR__ . '/../../../../../bootstrap.php';

$files = array_merge([
    $assetDirectory . '/pdfs/Brand-Guide.pdf',
    $assetDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetDirectory . '/pdfs/etown/Laboratory-Report.pdf',
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

$file = $_GET['f'];
require 'generate.php';
