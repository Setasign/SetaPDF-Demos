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

$demosDirectory = dirname(__DIR__) . '/demos';
$route = null;
$isDemo = false;
$callDemo = false;
$pathInfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
if (strpos($pathInfo, '/demo/') === 0) {
    $isDemo = true;
    $requestPath = trim(substr($pathInfo, strlen('/demo/')), '/');
} elseif (strpos($pathInfo, '/call-demo/') === 0) {
    $callDemo = true;
    $requestPath = trim(substr($pathInfo, strlen('/call-demo/')), '/');
    $requestedFile = substr($requestPath, strrpos($requestPath, '/'));
    $requestPath = substr($requestPath, 0, -strlen($requestedFile));
    $requestedFile .= '.php';
} else {
    $requestPath = trim($pathInfo, '/');
}

if (strpos($requestPath, '..') !== false || !is_dir($demosDirectory . '/' . $requestPath)) {
    header("HTTP/1.0 404 Not Found");
    ob_end_clean();
    return;
}

if ($callDemo) {
    /**
     * @var string $requestedFile
     */
    ob_end_clean();
    if (strpos($requestedFile, '..') !== false || !is_file($demosDirectory . '/' . $requestPath . $requestedFile)) {
        header("HTTP/1.0 404 Not Found");
        return;
    }

    // todo list of allowed files

    require_once $demosDirectory . '/' . $requestPath . $requestedFile;
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
    . '<div id="content">';

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
    } elseif (!isset($demoData['tabs'])) {
        $path = '/call-demo' . '/' . $requestPath . '/index';
        echo '<iframe src="' . $path . '" frameborder="0" style="width: 100%; height: 100%;"></iframe>';
    } else {
        $tabs = $demoData['tabs'];
        echo '<div class="setapdf-demo">'
            . '<div class="run"><ul>';
        foreach ($tabs as $tab) {
            echo '<li>'
                . '<a href="#' . $tab['id'] . '" title="' . $tab['title'] . '">'
                . $tab['icon'] . ' <span>' . $tab['title'] . '</span>'
                . '</a>'
                . '</li>';
        }
        echo '</ul></div>'
            . '<div class="demoTabPanel">';

        foreach ($tabs as $tab) {
            echo '<div class="step ' . $tab['id'] . '">';

            list($contentType, $content) = explode(':', $tab['content'], 2);
            if ($contentType === 'call') {
                $path = '/call-demo' . '/' . $requestPath . '/' . $content;
                echo '<iframe src="' . $path . '" frameborder="0" style="width: 100%; height: 100%;"></iframe>';
            } elseif ($contentType === 'preview') {
                $isPhp = true;
                echo '<div class="code"><ul class="buttons">'
                    . '<li><a href="#" class="copy"' . ($isPhp ? ' title="copy PHP code"' : '') . '>copy</a></li>'
                    . '</ul><pre class="code" data-lang="php">'
                    . htmlspecialchars(file_get_contents($demoDirectory . '/' . $content), ENT_QUOTES | ENT_HTML5)
                    . '</pre></div>';
            } else {
                throw new Exception(\sprintf('Invalid content type for tab "%s".', $contentType));
            }

            echo '</div>';
        }

        echo '</div>'
            . '<div class="run bottom"><ul>';
        foreach ($tabs as $tab) {
            echo '<li>'
                . '<a href="#' . $tab['id'] . '" title="' . $tab['title'] . '">'
                . $tab['icon'] . ' <span>' . $tab['title'] . '</span>'
                . '</a>'
                . '</li>';
        }
        echo '</ul></div>'
            . '</div>';
    }

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

        echo '<div class="demoDirectory">';

        if ($hasIcon) {
            echo '<a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
                . '<img alt="Demo Icon" src="data:image/png;base64,'
                    . base64_encode(file_get_contents($dir . '/icon.png')) . '"/>'
                . '</a>';
        }

        echo '<h3><a href="' . $path . '" title="' . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5) . '">'
            . htmlspecialchars($name, ENT_QUOTES | ENT_HTML5)
            . '</a></h3>'
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
