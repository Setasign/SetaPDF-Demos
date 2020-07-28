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
        <a href="http://www.setasign.com"><img src="/layout/img/small-logo.png" class="companyLogo" /></a>
    </div>
</header>
HTML;

$demosDirectory = __DIR__ . '/demos';
$route = null;
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
    . '<nav><ul itemscope itemtype="http://data-vocabulary.org/Breadcrumb">';

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

    echo '<div class="demo">'
        . (
            file_exists($demoDirectory . '/description.html')
            ? file_get_contents($demoDirectory . '/description.html')
            : ''
        );

    if (!$hasAllRequires) {
        echo '<p class="missingRequires"><h4>Missing requires</h4><ul>';
        foreach ($missingRequires as $missingRequire) {
            echo '<li>' . $missingRequire . '</li>';
        }
        echo '</ul></p>';
    } else {
        $previewFiles = array_merge(['script.php'], isset($demoData['previewFiles']) ? $demoData['previewFiles'] : []);

        echo '<div class="setapdf-demo' . (\count($previewFiles) > 1 ? ' extended' : '') . '">'
            . '<div class="run"><ul>';

        foreach ($previewFiles as $previewFile) {
            $className = md5($previewFile);
            echo '<li>'
                . '<a href="#' . $className . '" title="' . $previewFile . '">'
                . '&#xF121; <span>' . $previewFile . '</span>'
                . '</a>'
                . '</li>';
        }

        echo '<li><a href="#execute" title="Run">&#xF04B; <span>Run</span></a></li>'
            . '</ul></div>'
            . '<div class="demoTabPanel">';

        foreach ($previewFiles as $previewFile) {
            $className = md5($previewFile);
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

            echo '<div class="step ' . $className . '">'
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
            $className = md5($previewFile);
            echo '<li>'
                . '<a href="#' . $className . '" title="' . $previewFile . '">'
                . '&#xF121; <span>' . $previewFile . '</span>'
                . '</a>'
                . '</li>';
        }

        echo '<li><a href="#execute" title="Run">&#xF04B; <span>Run</span></a></li>'
            . '</ul>'
            . '</div>'
            . '</div>';
    }

    echo '</div>';
    echo '<div class="pageNavigation bottom"><a class="prev" href="#">Previous</a><a class="next" href="#">Next</a>';
    echo '</div>';
} else {
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
        $path = ($requestPath !== '' ? '/' . $requestPath : '') . '/' . basename($dir);

        $hasIcon = file_exists($dir . '/icon.png');

        echo '<div class="demoDirectory' . ($hasIcon ? ' withIcon' : '') . '">';

        if ($hasIcon) {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                    . base64_encode(file_get_contents($dir . '/icon.png')) . '"/>'
                . '</a>';
        }

        echo '<h2><a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
            . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
            . '</a></h2>'
            . '<p>' . htmlspecialchars($teaserText, ENT_QUOTES | ENT_HTML5) . '</p>'
            . '</div>';
    }

    /** @noinspection LowPerformingFilesystemOperationsInspection */
    foreach (glob($demosDirectory . ($requestPath !== '' ? '/' . $requestPath : '') . '/*/demo.json') as $demo) {
        $demoDirectory = dirname($demo);
        $demoData = json_decode(file_get_contents($demo), true);
        $name = isset($demoData['name']) ? $demoData['name'] : basename($demoDirectory);
        $teaserText = isset($demoData['teaserText']) ? $demoData['teaserText'] : '';
        $requires = isset($demoData['requires']) ? $demoData['requires'] : [];
        $hasIcon = file_exists($demoDirectory . '/icon.png');
        $path = '/demo' . ($requestPath !== '' ? '/' . $requestPath : '') . '/' . basename($demoDirectory);

        $hasAllRequires = true;
        $missingRequires = [];
        foreach ($requires as $require) {
            if (!in_array($require, $availablePackages, true)) {
                $hasAllRequires = false;
                $missingRequires[] = $require;
            }
        }

        echo '<div class="demoTeaser' . (!$hasAllRequires ? ' missingRequire' : '') . '">';
        if ($hasIcon) {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                . base64_encode(file_get_contents($demoDirectory . '/icon.png')) . '"/>'
                . '</a>';
        }

        echo '<h3><a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
            . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
            . '</a></h3>'
            . '<p>' . htmlspecialchars($teaserText, ENT_QUOTES | ENT_HTML5) . '</p>';
        if (!$hasAllRequires) {
            echo '<p class="missingRequires"><h4>Missing requires</h4><ul>';
            foreach ($missingRequires as $missingRequire) {
                echo '<li>' . $missingRequire . '</li>';
            }
            echo '</ul></p>';
        }
        echo '</div>';
    }
}

$year = date('Y');
echo <<<HTML
</div>
</div>
<footer>
    <div class="wrapper">
        <div class="copyright">
            ©{$year} Setasign GmbH &amp; Co. KG
            · <a href="https://www.setasign.com/contact/">Contact / Imprint</a>
            · <a href="https://www.setasign.com/data-privacy-statement/en/">Data Privacy Statement</a> (<a href="https://www.setasign.com/data-privacy-statement/de/">German</a>)
        </div>
    </div>
</footer>
<a href="#head" id="up"><i class="fa fa-arrow-circle-up"></i></a>
<script type="text/javascript" src="/js/script.js"></script>
</body>
</html>
HTML;

ob_end_flush();
