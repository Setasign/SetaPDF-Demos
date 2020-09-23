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
$sessionFiles = isset($_SESSION['files']) ? $_SESSION['files'] : [];
