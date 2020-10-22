<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '2500M');
date_default_timezone_set('Europe/Berlin');

if (is_file(__DIR__ . '/../library/SetaPDF/Autoload.php')) {
    // if the demos are bundled with setapdf
    require_once __DIR__ . '/../library/SetaPDF/Autoload.php';
} else {
    // if you use composer
    require_once __DIR__ . '/vendor/autoload.php';
}
// otherwise include your setapdf directory INSTEAD
//require_once __DIR__ . '/../SetaPDF/library/SetaPDF/Autoload.php';

session_start();
$assetsDirectory = __DIR__ . '/assets';
$classesDirectory = __DIR__ . '/classes';

$sessionFiles = isset($_SESSION['files']) ? $_SESSION['files'] : [];

function displayFiles($files, $iframe = true, $variants = [])
{
    if (!isset($_GET['f']) || !in_array($_GET['f'], $files, true)) {
        echo '<html><head><link rel="stylesheet" type="text/css" href="/layout/demo.css"/></head><body>';
        echo '<div id="fileSelector">';

        // list the files
        foreach ($files as $path) {
            $name = basename($path);
            if (count($variants) > 0) {
                foreach ($variants as $variantName => $_variants) {
                    foreach ($_variants as $variant) {
                        echo '<a href="?f=' . urlencode($path) . '&' . $variantName . '=' . $variant
                            . '"' . ($iframe ? ' target="pdfFrame"' : ''). '>'
                            . htmlspecialchars($name . ' (' .  $variantName . '=' . $variant . ')') . '</a><br />';
                    }
                }
            } else {
                echo '<a href="?f=' . urlencode($path) . '"' . ($iframe ? ' target="pdfFrame"' : ''). '>'
                    . htmlspecialchars($name) . '</a><br />';
            }
        }
        echo '</div>';

        if ($iframe) {
            echo '<iframe width="100%" height="300" name="pdfFrame" src="about:blank"/>';
        }

        echo '</body></html>';
        die();
    }
}
