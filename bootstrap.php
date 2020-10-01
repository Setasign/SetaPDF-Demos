<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '2500M');
date_default_timezone_set('Europe/Berlin');

// if you use composer
require_once __DIR__ . '/vendor/autoload.php';
// otherwise include your setapdf directory INSTEAD
//require_once __DIR__ . '/../SetaPDF/library/SetaPDF/Autoload.php';

session_start();
$assetsDirectory = __DIR__ . '/assets';
$classesDirectory = __DIR__ . '/classes';

$sessionFiles = isset($_SESSION['files']) ? $_SESSION['files'] : [];

function displayFiles($files, $iframe = true)
{
    if (!isset($_GET['f']) || !in_array($_GET['f'], $files, true)) {
        echo '<html><head><link rel="stylesheet" type="text/css" href="/layout/demo.css"/></head><body>';
        echo '<div id="fileSelector">';

        // list the files
        foreach ($files AS $path) {
            $name = basename($path);
            echo '<a href="?f=' . urlencode($path) . '"' . ($iframe ? ' target="pdfFrame"' : ''). '>' . htmlspecialchars($name) . '</a><br />';
        }
        echo '</div>';

        if ($iframe) {
            echo '<iframe width="100%" height="300" name="pdfFrame" src="about:blank"/>';
        }

        echo '</body></html>';
        die();
    }
}