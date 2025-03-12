<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\GoToAction;
use setasign\SetaPDF2\Core\Document\Action\JavaScriptAction;
use setasign\SetaPDF2\Core\Document\Action\NamedAction;
use setasign\SetaPDF2\Core\Document\Action\UriAction;
use setasign\SetaPDF2\Core\Reader\FileReader;

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Actions.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

$path = displayFiles($files);

// create a reader
$reader = new FileReader($path);
// create a document
$document = Document::load($reader);

// get the outlines helper
$outlines = $document->getCatalog()->getOutlines();
// or
// $outlines = $document->getOutlines();

// get the recursive iterator
$iterator = $outlines->getIterator();

// let's save the information in this array
$data = [];

// now iterate over the outline tree
foreach ($iterator AS $outlineItem) {
    // get the item depth and..
    $depth = $iterator->getDepth();

    // is the item opened or closed:
    $open = $outlineItem->isOpen();
    $title = $outlineItem->getTitle();

    $destinationOrAction = '';
    // get the destination of the outline item (if available)
    $destination = $outlineItem->getDestination($document);
    if ($destination !== false) {
        $destinationOrAction = 'Destination: Page ' . $destination->getPageNo($document);
    }

    // get the action of the outline item (if available)
    $action = $outlineItem->getAction();
    if ($action !== false) {
        $destinationOrAction = $action->getType() . ' Action';
        switch (true) {
            // handle GoTo Actions
            case $action instanceof GoToAction:
                $destination = $action->getDestination($document);
                $destinationOrAction .= ': Destination on Page ' . $destination->getPageNo($document);
                break;

            // handle Named Actions
            case $action instanceof NamedAction:
                $destinationOrAction .= ': ' . $action->getName();
                break;

            // handle JavaScript actions
            case $action instanceof JavaScriptAction:
                $destinationOrAction .= ': ' . substr($action->getJavaScript(), 0, 100);
                break;

            // handle URI actions
            case $action instanceof UriAction:
                $destinationOrAction .= ': ' . $action->getUri();
                break;
        }
    }

    // save item data
    $data[] = [
        'depth' => $depth,
        'open' => $open,
        'title' => $title,
        'destinationOrAction' => $destinationOrAction
    ];
}

echo <<<HTML
<table border="1" style="width:100%;">
    <tr>
        <th width="10%">Depth</th>
        <th width="10%">Open</th>
        <th width="35%">Title</th>
        <th width="45%">Destination / Action</th>
    </tr>
HTML;
foreach ($data AS $itemData) {
    echo '<tr>'
        . '<td>' . $itemData['depth'] . '</td>'
        . '<td>' . ($itemData['open'] !== null ? '-' : '+') . '</td>'
        . '<td>' . str_repeat('&nbsp;', $itemData['depth'] * 4) . htmlspecialchars($itemData['title']) . '</td>'
        . '<td>' . htmlspecialchars($itemData['destinationOrAction']) . '</td>'
        . '</tr>';
}

echo <<<HTML
</table>
HTML;
