<?php

if (PHP_SAPI === 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . preg_replace('~\?(.*)$~', '', $_SERVER['REQUEST_URI']);
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../bootstrap.php';

$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
$base = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', dirname($scriptName)), '/') . '/';

$demosDirectory = __DIR__ . '/demos';
$requestPath = isset($_GET['p']) ? $_GET['p'] : '';
$isDemo = (strpos($requestPath, '/demo/') === 0);
if ($requestPath === 'previewFile') {
    $file = isset($_GET['f']) ? $_GET['f'] : '';
    if (strpos($file, '/assets/') === 0 && strpos($file, '..') === false) {
        $file = __DIR__ . '/..' . $file;

        if (!is_file($file)) {
            header("HTTP/1.0 404 Not Found");
            ob_end_clean();
            return;
        }
    } else {
        $file = realpath($demosDirectory . $file);
        if ($file === false || strpos($file, realpath($demosDirectory)) !== 0) {
            header("HTTP/1.0 404 Not Found");
            return;
        }
    }

    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if ($extension === 'pdf') {
        $contentType = 'application/pdf';
    } elseif ($extension === 'jpg') {
        $contentType = 'image/jpeg';
    } elseif ($extension === 'png') {
        $contentType = 'image/png';
    } elseif ($extension === 'gif') {
        $contentType = 'image/gif';
    } else {
        throw new RuntimeException('Unknown extension!');
    }
    $inline = isset($_GET['inline']) ? $_GET['inline'] : true;
    header('Content-Type: ' . $contentType);
    if ($inline) {
        header('Content-Disposition: inline; filename="' . basename($file) . '";');
    } else {
        header('Content-Disposition: attachment; filename="' . basename($file) . '";');
    }
    header('Content-Length: ' . filesize($file));
    echo file_get_contents($file);

    return;
}

$requestPath = trim($isDemo ? substr($requestPath, strlen('/demo/')) : $requestPath, '/');

if (strpos($requestPath, '..') !== false || !is_dir($demosDirectory . '/' . $requestPath)) {
    header("HTTP/1.0 404 Not Found");
    ob_end_clean();
    return;
}

ob_start();
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SetaPDF Demos</title>
    <base href="{$base}"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="./layout/normalize.css"/>
    <link rel="stylesheet" type="text/css" href="./layout/style.css"/>
    <link rel="stylesheet" type="text/css" href="./js/codemirror-5.61.1/codemirror.css"/>
    <script type="text/javascript" src="./js/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="./js/codemirror-5.61.1/codemirror.js"></script>
    <script type="text/javascript" src="./js/clipboard.js"></script>
</head>
<body>
<header>
    <div class="wrapper default">
        <h1>SetaPDF Demos</h1>
        <a href="http://www.setasign.com"><img src="./layout/img/small-logo.png" class="companyLogo" alt="Logo"/></a>
    </div>
</header>
HTML;

$availablePackages = [];
if (class_exists(SetaPDF_Core::class)) {
    $availablePackages[] = 'SetaPDF-Core';
}
if (class_exists(SetaPDF_Extractor::class)) {
    $availablePackages[] = 'SetaPDF-Extractor';
}
if (class_exists(SetaPDF_FormFiller::class)) {
    $availablePackages[] = 'SetaPDF-FormFiller';
    if (class_exists(SetaPDF_FormFiller_Field_List::class)) {
        $availablePackages[] = 'SetaPDF-FormFiller Full';
    }
}
if (class_exists(SetaPDF_Merger::class)) {
    $availablePackages[] = 'SetaPDF-Merger';
}
if (class_exists(SetaPDF_Signer::class)) {
    $availablePackages[] = 'SetaPDF-Signer';
}
if (class_exists(SetaPDF_Stamper::class)) {
    $availablePackages[] = 'SetaPDF-Stamper';
}


echo '<div id="breadcrumb"><div class="wrapper">'
    . '<nav><ul>';

$breadCrumb = [
    ['path' => '/', 'text' => 'Demos']
];

$fullPath = '/';
foreach (explode('/', $requestPath) as $pathPart) {
    if ($pathPart === '') {
        continue;
    }

    $fullPath .= $pathPart . '/';
    $metaData = [];
    if (file_exists($demosDirectory . $fullPath . 'demo.json')) {
        $metaData = json_decode(file_get_contents($demosDirectory . $fullPath . 'demo.json'), true);
        $breadCrumb[] = [
            'path' => '/demo' . $fullPath,
            'text' => isset($metaData['name']) ? $metaData['name'] : $pathPart
        ];
    } elseif (file_exists($demosDirectory . $fullPath . 'meta.json')) {
        $metaData = json_decode(file_get_contents($demosDirectory . $fullPath . 'meta.json'), true);
        $breadCrumb[] = [
            'path' => $fullPath,
            'text' => isset($metaData['name']) ? $metaData['name'] : $pathPart
        ];
    } else {
        $breadCrumb[] = [
            'path' => $fullPath,
            'text' => $pathPart
        ];
    }
}
unset($fullPath);

foreach ($breadCrumb as $crumb) {
    echo '<li itemprop="title"><a itemprop="url" href="?p=' . urlencode($crumb['path']) . '">'
        . $crumb['text']
        . '</a></li>';
}

echo '</ul></nav></div></div>'
    . '<div id="content"><div class="wrapper">';

if ($isDemo) {
    $demoDirectory = $demosDirectory . '/' . $requestPath;
    if (!file_exists($demoDirectory . '/demo.json')) {
        header("HTTP/1.0 404 Not Found");
        ob_end_clean();
        return;
    }
    $demoData = json_decode(file_get_contents($demoDirectory . '/demo.json'), true);
    $name = isset($demoData['name']) ? $demoData['name'] : basename($demoDirectory);
    $requires = isset($demoData['requires']) ? $demoData['requires'] : [];

    $hasAllRequires = true;
    $missingRequires = [];
    foreach ($requires as $require) {
        if (!in_array($require, $availablePackages, true)) {
            $hasAllRequires = false;
            $missingRequires[] = $require;
        }
    }

    $previousDemos = [];
    $nextDemos = [];
    $currentDemoFound = false;
    $demoPaths = glob(dirname($demoDirectory) . '/*/demo.json', GLOB_NOSORT);
    sort($demoPaths, SORT_NATURAL);
    foreach ($demoPaths as $actualDemo) {
        $actualDemoDirectory = dirname($actualDemo);
        $actualDemoData = json_decode(file_get_contents($actualDemo), true);
        $actualDemoName = isset($actualDemoData['name']) ? $actualDemoData['name'] : basename($actualDemoDirectory);
        $actualDemoPath = '/demo' . substr($actualDemoDirectory, strlen($demosDirectory));

        if ($actualDemoDirectory === $demoDirectory) {
            $currentDemoFound = true;
        } elseif ($currentDemoFound) {
            $nextDemos[] = [
                'name' => $actualDemoName,
                'path' => $actualDemoPath,
            ];
        } else {
            $previousDemos[] = [
                'name' => $actualDemoName,
                'path' => $actualDemoPath
            ];
        }

        unset($actualDemoDirectory, $actualDemoData, $actualDemoName, $actualDemoPath);
    }

    echo '<div class="demo">';
    $gitHub = 'https://github.com/Setasign/SetaPDF-Demos/tree/master/public/demos/' . $requestPath . '/script.php';
    echo '<h2><a href="' . $gitHub . '" target="_blank" class="github" title="View on GitHub"></a>'
        . htmlentities($name, ENT_QUOTES, "UTF-8") . '</h2>';

    if (file_exists($demoDirectory . '/description.html')) {
        echo file_get_contents($demoDirectory . '/description.html');
    } elseif (isset($demoData['teaserText'])) {
        echo '<p>' . $demoData['teaserText'] . '</p>';
    }

    $previewFiles = array_merge(['script.php'], isset($demoData['previewFiles']) ? $demoData['previewFiles'] : []);

    echo '<div class="setapdf-demo' . (count($previewFiles) > 1 ? ' extended' : '') . '">'
        . '<div class="run"><ul>';

    foreach ($previewFiles as $previewFile) {
        $previewFileIdent = md5($previewFile);
        $previewFile = basename($previewFile);
        $extension = strtolower(pathinfo($previewFile, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            $icon = '&#xF1C1;';
        } elseif (in_array($extension, ['jpg', 'png', 'gif'], true)) {
            $icon = '&#xF1C5';
        } else {
            $icon = '&#xF121;';
        }

        echo '<li>'
            . '<a href="#' . $previewFileIdent . '" title="' . $previewFile . '">'
            . $icon . ' <span>' . $previewFile . '</span>'
            . '</a>'
            . '</li>';
    }

    $runTitle = 'Run';
    if (!$hasAllRequires) {
        $runTitle = 'To execute this demo following dependencies are missing: ' . implode(', ', $missingRequires);
    }
    echo '<li><a href="#execute" title="' . $runTitle . '"' . ($hasAllRequires ? '' : ' class="disabled"')
        . '>&#xF04B; <span>Run</span></a></li>'
        . '</ul></div>'
        . '<div class="demoTabPanel">';

    foreach ($previewFiles as $previewFile) {
        $previewFileIdent = md5($previewFile);
        $extension = strtolower(pathinfo($previewFile, PATHINFO_EXTENSION));
        if (in_array($extension, ['pdf', 'jpg', 'png', 'gif'], true)) {
            if (strpos($previewFile, '/assets/') !== 0) {
                $previewFile = '/' . $requestPath . '/' . $previewFile;
            }

            echo '<div class="step ' . $previewFileIdent . '">'
                . '<iframe data-src="?p=previewFile&f=' . $previewFile . '" src="about:blank"'
                . ' frameborder="0" style="width: 100%; height: 100%;"></iframe>'
                . '</div>';
            continue;
        }

        switch ($extension) {
            case 'php':
                $codemirrorLang = 'php';
                break;
            case 'js':
                $codemirrorLang = 'javascript';
                break;
            default:
                throw new Exception(sprintf('Unknown extension "%s".', $extension));
        }

        echo '<div class="step ' . $previewFileIdent . '">'
            . '<div class="code">'
            . ($codemirrorLang === 'php' ? '<div class="phpInfo" title="The PHP source code that is executed by this demo.">PHP</div>' : '')
            . '<ul class="buttons">'
            . '<li><a href="?p=' . urlencode($_GET['p']) . '#" class="copy"'
            . ($codemirrorLang === 'php' ? ' title="copy PHP code"' : '') . '>copy</a></li>'
            . '</ul><pre class="code" data-lang="' . $codemirrorLang . '">'
            . htmlspecialchars(file_get_contents($demoDirectory . '/' . $previewFile), ENT_QUOTES | ENT_HTML5)
            . '</pre></div>'
            . '</div>';
    }

    echo '<div class="step execute">'
        . '<iframe data-src="./demos/' . $requestPath . '/script.php" src="data:text/html;base64,'
        . base64_encode(isset($demoData['iframePlaceholder']) ? $demoData['iframePlaceholder'] : 'Download started...')
        . '" frameborder="0" style="width: 100%; height: 100%;">'
        . '</iframe>'
        . '</div>'
        . '</div>';

    echo '<div class="run bottom"><ul>';

    foreach ($previewFiles as $previewFile) {
        $previewFileIdent = md5($previewFile);
        $previewFile = basename($previewFile);
        $extension = strtolower(pathinfo($previewFile, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            $icon = '&#xF1C1;';
        } elseif (in_array($extension, ['jpg', 'png', 'gif'], true)) {
            $icon = '&#xF1C5';
        } else {
            $icon = '&#xF121;';
        }

        echo '<li>'
            . '<a href="#' . $previewFileIdent . '" title="' . $previewFile . '">'
            . $icon . ' <span>' . $previewFile . '</span>'
            . '</a>'
            . '</li>';
    }

    echo '<li><a href="#execute" title="Run"' . ($hasAllRequires ? '' : ' class="disabled"') . '>'
        . '&#xF04B; <span>Run</span></a></li>'
        . '</ul>'
        . '</div>'
        . '</div>';

    echo '</div>';

    echo '<div class="pageNavigation bottom">';
    if (count($nextDemos) > 0) {
        $nextDemo = array_shift($nextDemos);
        echo '<span>'
           . '<a href="?p=' . urlencode($nextDemo['path']) . '" class="next" title="' . $nextDemo['name'] . '">'
            . $nextDemo['name']
            . '</a>';

        /** @noinspection NotOptimalIfConditionsInspection */
        if (count($nextDemos) > 0) {
            echo '<div class="others"><ul>';
            foreach (array_reverse($nextDemos) as $nextDemo) {
                echo '<li><a href="?p=' . urlencode($nextDemo['path']) . '">' . $nextDemo['name'] . '</a></li>';
            }
            echo '</ul></div>';
        }
        echo '</span>';
    } else {
        echo '<span class="emptyNext">&nbsp;</span>';
    }

    if (count($previousDemos) > 0) {
        $previousDemo = array_pop($previousDemos);
        echo '<span>'
            . '<a href="?p=' . urlencode($previousDemo['path']) . '" class="prev" title="' . $previousDemo['name'] . '">'
            . $previousDemo['name']
            . '</a>';

        /** @noinspection NotOptimalIfConditionsInspection */
        if (count($previousDemos) > 0) {
            echo '<div class="others"><ul>';
            foreach ($previousDemos as $previousDemo) {
                echo '<li><a href="?p=' . urlencode($previousDemo['path']) . '">' . $previousDemo['name'] . '</a></li>';
            }
            echo '</ul></div>';
        }

        echo '</span>';
    } else {
        echo '<span class="emptyPrev">&nbsp;</span>';
    }
    echo '</div>'
        . '</div>';
} else {
    echo '<h2>' . (isset($metaData['name']) ? $metaData['name'] : end($breadCrumb)['text']) . '</h2>';

    if (file_exists($demosDirectory . '/' . $requestPath . '/description.html')) {
        echo file_get_contents($demosDirectory . '/' . $requestPath . '/description.html');
    } elseif (isset($metaData['teaserText'])) {
        echo '<p>' . $metaData['teaserText'] . '</p>';
    }

    echo '<div class="directoriesWrapper">';
    $demoDirs = glob($demosDirectory . ($requestPath !== '' ? '/' . $requestPath : '') . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
    sort($demoDirs, SORT_NATURAL);
    foreach ($demoDirs as $dir) {
        if (file_exists($dir . '/demo.json')) {
            continue;
        }
        $metaData = [];
        if (file_exists($dir . '/meta.json')) {
            $metaData = json_decode(file_get_contents($dir . '/meta.json'), true);
        }

        $name = isset($metaData['name']) ? $metaData['name'] : basename($dir);
        $teaserText = isset($metaData['teaserText']) ? $metaData['teaserText'] : '';
        $path = substr($dir, strlen($demosDirectory));
        $requires = isset($metaData['requires']) ? $metaData['requires'] : [];
        $hasIcon = file_exists($dir . '/icon.png');
        $faIcon = isset($metaData['faIcon']) ? $metaData['faIcon'] : '&#xf07c;';
        $faIcon2 = isset($metaData['faIcon2']) ? $metaData['faIcon2'] : false;

        $missingRequires = [];
        foreach ($requires as $require) {
            if (!in_array($require, $availablePackages, true)) {
                $missingRequires[] = $require;
            }
        }

        echo '<div class="demoDirectory' . (count($missingRequires) > 0 ? ' missingRequire' : '') . '">';

        if ($hasIcon) {
            echo '<a href="?p=' . urlencode($path) . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                    . base64_encode(file_get_contents($dir . '/icon.png')) . '"/>'
                . '</a>';
        } else {
            echo '<a href="?p=' . urlencode($path) . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
                . '" class="teaserIcon" data-faIcon="' . $faIcon . '"'
                . (($faIcon2) ? ' data-faIcon2="' . $faIcon2 . '"' : '')
                . '></a>';
        }

        echo '<h2><a href="?p=' . urlencode($path) . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
            . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
            . '</a>';
        if (count($missingRequires) > 0) {
            $tip = 'To execute this demo following dependencies are missing: ' . implode(', ', $missingRequires);
            echo ' <span class="missingRequireTip" title="' . htmlspecialchars($tip, ENT_QUOTES | ENT_HTML5) . '">&#xf05a;</span>';
        }
        echo '</h2>'
            . '<p>' . htmlspecialchars($teaserText, ENT_QUOTES | ENT_HTML5) . '</p>'
            . '</div>';
    }
    echo '</div>';

    echo '<div class="demoTeaserWrapper">';

    $demoPaths = glob($demosDirectory . ($requestPath !== '' ? '/' . $requestPath : '') . '/*/demo.json', GLOB_NOSORT);
    sort($demoPaths, SORT_NATURAL);
    /** @noinspection LowPerformingFilesystemOperationsInspection */
    foreach ($demoPaths as $demo) {
        $demoDirectory = dirname($demo);
        $demoData = json_decode(file_get_contents($demo), true);
        $name = isset($demoData['name']) ? $demoData['name'] : basename($demoDirectory);
        $teaserText = isset($demoData['teaserText']) ? $demoData['teaserText'] : '';
        $requires = isset($demoData['requires']) ? $demoData['requires'] : [];
        $hasIcon = file_exists($demoDirectory . '/icon.png');
        $path = '/demo' . substr($demoDirectory, strlen($demosDirectory));
        $faIcon = isset($demoData['faIcon']) ? $demoData['faIcon'] : '&#xf121;';
        $faIcon2 = isset($demoData['faIcon2']) ? $demoData['faIcon2'] : false;

        $missingRequires = [];
        foreach ($requires as $require) {
            if (!in_array($require, $availablePackages, true)) {
                $missingRequires[] = $require;
            }
        }

        echo '<div class="demoTeaser' . (count($missingRequires) > 0 ? ' missingRequire' : '') . '">';
        if ($hasIcon) {
            echo '<a href="?p=' . urlencode($path) . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                . base64_encode(file_get_contents($demoDirectory . '/icon.png')) . '"/>'
                . '</a>';
        } else {
            echo '<a href="?p=' . urlencode($path) . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
                . '" class="teaserIcon" data-faIcon="' . $faIcon . '"'
                . (($faIcon2) ? ' data-faIcon2="' . $faIcon2 . '"' : '')
                . '></a>';
        }

        echo '<h3><a href="?p=' . urlencode($path) . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
            . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
            . '</a>';

        if (count($missingRequires) > 0) {
            $tip = 'To execute this demo following dependencies are missing: ' . implode(', ', $missingRequires);
            echo ' <span class="missingRequireTip" title="' . htmlspecialchars($tip, ENT_QUOTES | ENT_HTML5) . '">&#xf05a;</span>';
        }

        echo '</h3>';
        echo '<p>' . htmlspecialchars($teaserText, ENT_QUOTES | ENT_HTML5) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

$year = date('Y');
echo <<<HTML
</div>
</div>
<footer>
    <div class="wrapper">
        <div class="copyright">
            ©{$year} <a href="https://www.setasign.com/">Setasign GmbH &amp; Co. KG</a>
        </div>
    </div>
</footer>
<script type="text/javascript" src="./js/script.js"></script>
</body>
</html>
HTML;

ob_end_flush();
