<?php

error_reporting(E_ALL);
ini_set('display_errors', $_SERVER['SERVER_NAME'] === 'localhost' ? 1 : 0);
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

$assetsDirectory = __DIR__ . '/assets';
$classesDirectory = __DIR__ . '/classes';

$dir = dirname($_SERVER['SCRIPT_NAME']);
if (strpos($dir, '/public/') !== false) {
    $basePath = substr($dir, 0, strpos($dir, '/public/')) . '/public/';
} else {
    $basePath = '/';
}

function displayFiles($files, $iframe = true, $multiple = false, $upload = false)
{
    if (isset($_GET['f'])) {
        if ($multiple) {
            if (is_array($_GET['f'])) {
                $result = [];
                foreach ($_GET['f'] as $f) {
                    if (is_scalar($f) && isset($files[$f])) {
                        $result[] = $files[$f];
                    }
                }

                if (count($result) > 0) {
                    return $result;
                }
            }
        } else {
            if (is_scalar($_GET['f']) && isset($files[$_GET['f']])) {
                return $files[$_GET['f']];
            }
        }
    }

    if ($upload) {
        if (isset($_FILES['upload']) && $_FILES['upload']['error'] === 0) {
            return [
                'file' => $_FILES['upload']['tmp_name'],
                'filename' => $_FILES['upload']['name']
            ];
        }
    }

    echo '<html><head>';
    echo '<link rel="stylesheet" type="text/css" href="' . $GLOBALS['basePath'] . 'layout/demo.css"/></head><body>';
    echo '<form id="demoInput"' . ($iframe ? ' target="pdfFrame"' : '');
    if ($upload) {
        echo ' method="post" enctype="multipart/form-data"';
    }
    echo '>';

    if ($upload) {
        echo '<div class="uploadRow"><input type="file" name="upload" /><input type="submit" /></div>';
    }

    // list the files
    foreach ($files as $f => $path) {
        if (is_array($path)) {
            $displayValue = isset($path['displayValue']) ? $path['displayValue'] : null;
        } else {
            $displayValue = basename($path);
        }

        if ($multiple) {
            echo '<label for="file'. $f . '">';
            echo '<input type="checkbox" name="f[]" value="' . $f . '" id="file'. $f . '" onclick="checkSubmitBtn(this)" />';
            echo htmlspecialchars($displayValue) . '</label><br />';
        } else {
            echo '<a href="?' . http_build_query(['f' => $f]) . '"' . ($iframe ? ' target="pdfFrame"' : ''). '>'
                . htmlspecialchars($displayValue) . '</a><br />';
        }
    }
    if ($multiple) {
        echo '<script>checkSubmitBtn=function(e){var d=0,btn=document.getElementById("submitBtn");document.getElementsByName("f[]").forEach(function(n){d|=n.checked});btn.disabled=!d;}</script>';
        echo '<input type="submit" value="run" id="submitBtn" disabled/>';
    }
    echo '</form>';

    if ($iframe) {
        echo '<iframe width="100%" name="pdfFrame" src="about:blank"/>';
    }

    echo '</body></html>';
    die();
}

function displaySelect($label, $data, $iframe = true, $displayValueKey = null)
{
    if (!isset($_GET['data']) || !array_key_exists($_GET['data'], $data)) {
        echo '<html><head>';
        echo '<link rel="stylesheet" type="text/css" href="' . $GLOBALS['basePath'] . 'layout/demo.css"/></head><body>';
        echo '<div id="demoInput">';

        echo '<form method="GET"';
        if ($iframe) {
            echo ' target="pdfFrame"';
        }
        echo '><label for="data">' . htmlspecialchars($label) . '</label>'
            . '<select id="data" name="data" onchange="if(this.value)this.form.submit();">'
            . '<option value="">--- please choose ---</option>';
        foreach ($data as $value => $displayValue) {
            if (is_array($displayValue)) {
                $displayValueKey = $displayValueKey !== null ? $displayValueKey : key($displayValue);
                $displayValue = $displayValue[$displayValueKey];
            }
            echo '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($displayValue) . '</option>';
        }
        echo '</select></form></div>';

        if ($iframe) {
            echo '<iframe width="100%" name="pdfFrame" src="about:blank"/>';
        }

        echo '</body></html>';
        die();
    }

    return $_GET['data'];
}
