<?php

if (PHP_SAPI === 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

require_once __DIR__ . '/../bootstrap.php';

ob_start();
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SetaPDF Demos</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" type="text/css" href="/layout/normalize.css"/>
    <link rel="stylesheet" type="text/css" href="/layout/style.css"/>
    <link rel="stylesheet" type="text/css" href="/js/codemirror-5.11/lib/codemirror.css"/>
    <script type="text/javascript" src="/js/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="/js/codemirror-5.11/lib/codemirror.min.js"></script>
    <script type="text/javascript" src="/js/clipboard.js"></script>
</head>
<body>
<header>
    <div class="wrapper default">
        <h1>SetaPDF Demos</h1>
        <a href="http://www.setasign.com"><img src="/layout/img/small-logo.png" class="companyLogo" alt="Logo"/></a>
    </div>
</header>
HTML;

$demosDirectory = __DIR__ . '/demos';
$isDemo = false;
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
if (strpos($pathInfo, '/demo/') === 0) {
    $isDemo = true;
    $requestPath = trim(substr($pathInfo, strlen('/demo/')), '/');
} else {
    $requestPath = trim($pathInfo, '/');
}

if (strpos($requestPath, '..') !== false || !is_dir($demosDirectory . '/' . $requestPath)) {
    header("HTTP/1.0 404 Not Found");
    ob_end_clean();
    return;
}

$availablePackages = [];
if (class_exists(SetaPDF_Core::class)) {
    $availablePackages[] = 'SetaPDF-Core';
}
if (class_exists(SetaPDF_Extractor::class)) {
    $availablePackages[] = 'SetaPDF-Extractor';
}
if (class_exists(SetaPDF_FormFiller::class)) {
    $availablePackages[] = 'SetaPDF-FormFiller';
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
    echo '<li itemprop="title"><a itemprop="url" href="' . $crumb['path'] . '">' . $crumb['text'] . '</a></li>';
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
    foreach (glob(dirname($demoDirectory) . '/*/demo.json') as $actualDemo) {
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

    echo '<div class="demo">'
        . '<h2>' . htmlentities($name, ENT_QUOTES, "UTF-8") . '</h2>';

    if (file_exists($demoDirectory . '/description.html')) {
        echo file_get_contents($demoDirectory . '/description.html');
    } elseif (isset($demoData['teaserText'])) {
        echo '<p>' . $demoData['teaserText'] . '</p>';
    }

    $previewFiles = array_merge(['script.php'], isset($demoData['previewFiles']) ? $demoData['previewFiles'] : []);

    echo '<div class="setapdf-demo' . (\count($previewFiles) > 1 ? ' extended' : '') . '">'
        . '<div class="run"><ul>';

    foreach ($previewFiles as $previewFile) {
        $tabHasg = md5($previewFile);
        $previewFile = basename($previewFile);
        echo '<li>'
            . '<a href="#' . $tabHasg . '" title="' . $previewFile . '">'
            . '&#xF121; <span>' . $previewFile . '</span>'
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
        $tabHasg = md5($previewFile);
        $extension = strtolower(pathinfo($demoDirectory . '/' . $previewFile, PATHINFO_EXTENSION));
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

        echo '<div class="step ' . $tabHasg . '">'
            . '<div class="code">'
            . ($codemirrorLang === 'php' ? '<div class="phpInfo" title="The PHP source code that is executed by this demo.">PHP</div>' : '')
            . '<ul class="buttons">'
            . '<li><a href="#" class="copy"' . ($codemirrorLang === 'php' ? ' title="copy PHP code"' : '') . '>copy</a></li>'
            . '</ul><pre class="code" data-lang="' . $codemirrorLang . '">'
            . htmlspecialchars(file_get_contents($demoDirectory . '/' . $previewFile), ENT_QUOTES | ENT_HTML5)
            . '</pre></div>'
            . '</div>';
    }

    echo '<div class="step execute">'
        . '<iframe src="/demos/' . $requestPath . '/script.php" frameborder="0" style="width: 100%; height: 100%;">'
        . '</iframe>'
        . '</div>'
        . '</div>';

    echo '<div class="run bottom"><ul>';

    foreach ($previewFiles as $previewFile) {
        $tabHasg = md5($previewFile);
        $previewFile = basename($previewFile);
        echo '<li>'
            . '<a href="#' . $tabHasg . '" title="' . $previewFile . '">'
            . '&#xF121; <span>' . $previewFile . '</span>'
            . '</a>'
            . '</li>';
    }

    echo '<li><a href="#execute" title="Run"' . ($hasAllRequires ? '' : ' class="disabled"') . '>&#xF04B; <span>Run</span></a></li>'
        . '</ul>'
        . '</div>'
        . '</div>';

    echo '</div>';

    echo '<div class="pageNavigation bottom">';
    if (count($nextDemos) > 0) {
        $nextDemo = array_shift($nextDemos);
        echo '<span>'
           . '<a href="' . $nextDemo['path'] . '" class="next" title="' . $nextDemo['name'] . '">'
            . $nextDemo['name']
            . '</a>';

        /** @noinspection NotOptimalIfConditionsInspection */
        if (count($nextDemos) > 0) {
            echo '<div class="others"><ul>';
            foreach ($nextDemos as $nextDemo) {
                echo '<li><a href="' . $nextDemo['path'] . '">' . $nextDemo['name'] . '</a></li>';
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
            . '<a href="' . $previousDemo['path'] . '" class="prev" title="' . $previousDemo['name'] . '">'
            . $previousDemo['name']
            . '</a>';

        /** @noinspection NotOptimalIfConditionsInspection */
        if (count($previousDemos) > 0) {
            echo '<div class="others"><ul>';
            foreach ($previousDemos as $previousDemo) {
                echo '<li><a href="' . $previousDemo['path'] . '">' . $previousDemo['name'] . '</a></li>';
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

    echo '<h2>' . (isset($metaData['name']) ? $metaData['name'] : $pathPart) . '</h2>';

    if (file_exists($demosDirectory . '/' . $requestPath . '/description.html')) {
        echo file_get_contents($demosDirectory . '/' . $requestPath . '/description.html');
    } elseif (isset($metaData['teaserText'])) {
        echo '<p>' . $metaData['teaserText'] . '</p>';
    }

    echo '<div class="directoriesWrapper">';
    foreach (glob($demosDirectory . ($requestPath !== '' ? '/' . $requestPath : '') . '/*', GLOB_ONLYDIR) as $dir) {
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

        $missingRequires = [];
        foreach ($requires as $require) {
            if (!in_array($require, $availablePackages, true)) {
                $missingRequires[] = $require;
            }
        }

        echo '<div class="demoDirectory' . (count($missingRequires) > 0 ? ' missingRequire' : '') . '">';

        if ($hasIcon) {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                    . base64_encode(file_get_contents($dir . '/icon.png')) . '"/>'
                . '</a>';
        } else {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
                . '" class="teaserIcon" data-faIcon="' . $faIcon . '"></a>';
        }

        echo '<h2><a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
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

    /** @noinspection LowPerformingFilesystemOperationsInspection */
    foreach (glob($demosDirectory . ($requestPath !== '' ? '/' . $requestPath : '') . '/*/demo.json') as $demo) {
        $demoDirectory = dirname($demo);
        $demoData = json_decode(file_get_contents($demo), true);
        $name = isset($demoData['name']) ? $demoData['name'] : basename($demoDirectory);
        $teaserText = isset($demoData['teaserText']) ? $demoData['teaserText'] : '';
        $requires = isset($demoData['requires']) ? $demoData['requires'] : [];
        $hasIcon = file_exists($demoDirectory . '/icon.png');
        $path = '/demo' . substr($demoDirectory, strlen($demosDirectory));
        $faIcon = isset($demoData['faIcon']) ? $demoData['faIcon'] : '&#xf121;';

        $missingRequires = [];
        foreach ($requires as $require) {
            if (!in_array($require, $availablePackages, true)) {
                $missingRequires[] = $require;
            }
        }

        echo '<div class="demoTeaser' . (count($missingRequires) > 0 ? ' missingRequire' : '') . '">';
        if ($hasIcon) {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                . base64_encode(file_get_contents($demoDirectory . '/icon.png')) . '"/>'
                . '</a>';
        } else {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
                . '" class="teaserIcon" data-faIcon="' . $faIcon . '"></a>';
        }

        echo '<h3><a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
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
<script type="text/javascript" src="/js/script.js"></script>
</body>
</html>
HTML;

ob_end_flush();
