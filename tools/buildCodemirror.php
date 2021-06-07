<?php

$codemirror = '/path/to/codemirror-5.61.1';
$version = '5.61.1';
$targetDirectory = __DIR__ . '/../public/js/codemirror-' . $version;

$modes = [
    'htmlembedded',
    'htmlmixed',
    'javascript',
    'smarty',
    'sql',
    'xml',
    'clike', // required for php
    'php'
];

$addonsJs = [
    'edit/closetag.js',
    'runmode/colorize.js',
    'edit/matchtags.js',
    'runmode/runmode.js',
    'fold/xml-fold.js',
];

$addonsCss = [];

$jsFile = [];
$cssFile = ["/*codemirror version $version*/"];

$jsFile[] = file_get_contents($codemirror . '/lib/codemirror.js');
$cssFile[] = file_get_contents($codemirror . '/lib/codemirror.css');

foreach ($modes as $mode) {
    $jsFile[] = file_get_contents($codemirror . '/mode/' . $mode . '/' . $mode . '.js');
}

foreach ($addonsJs as $addonFile) {
    $jsFile[] = file_get_contents($codemirror . '/addon/' . $addonFile);
}

foreach ($addonsCss as $addonFile) {
    $cssFile[] = file_get_contents($codemirror . '/addon/' . $addonFile);
}

$jsFile = implode("\n", $jsFile);
$cssFile = implode("\n", $cssFile);

$jsFileName = $targetDirectory . '/codemirror.js';
$cssFileName = $targetDirectory . '/codemirror.css';

file_put_contents($jsFileName, $jsFile);
file_put_contents($cssFileName, $cssFile);

`uglifyjs $jsFileName -o $jsFileName -b "beautify=false,preamble='/*codemirror version $version*/'"`;
