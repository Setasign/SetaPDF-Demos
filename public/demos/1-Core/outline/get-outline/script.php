<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

// prepare some files
$files = [
    $assetsDirectory . '/pdfs/Actions.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

$path = displayFiles($files);

// create a reader
$reader = new SetaPDF_Core_Reader_File($path);
// create a document
$document = SetaPDF_Core_Document::load($reader);

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

    $destionationOrAction = '';
    // get the destination of the outline item (if available)
    $destination = $outlineItem->getDestination($document);
    if ($destination !== false) {
        $destionationOrAction = 'Destination: Page ' . $destination->getPageNo($document);
    }

    // get the action of the outline item (if available)
    $action = $outlineItem->getAction();
    if ($action !== false) {
        $destionationOrAction = $action->getType() . ' Action';
        switch (true) {
            // handle GoTo Actions
            case $action instanceof SetaPDF_Core_Document_Action_GoTo:
                $destination = $action->getDestination($document);
                $destionationOrAction .= ': Destination on Page ' . $destination->getPageNo($document);
                break;

            // handle Named Actions
            case $action instanceof SetaPDF_Core_Document_Action_Named:
                $destionationOrAction .= ': ' . $action->getName();
                break;

            // handle JavaScript actions
            case $action instanceof SetaPDF_Core_Document_Action_JavaScript:
                $destionationOrAction .= ': ' . substr($action->getJavaScript(), 0, 100);
                break;

            // handle URI actions
            case $action instanceof SetaPDF_Core_Document_Action_Uri:
                $destionationOrAction .= ': ' . $action->getUri();
                break;
        }
    }

    // save item data
    $data[] = [
        'depth' => $depth,
        'open' => $open,
        'title' => $title,
        'destinationOrAction' => $destionationOrAction
    ];
}

?>

<table border="1" style="width:100%;">
    <tr>
        <th width="10%">Depth</th>
        <th width="10%">Open</th>
        <th width="35%">Title</th>
        <th width="45%">Destination / Action</th>
    </tr>
    <?php foreach ($data AS $itemData): ?>
        <tr>
            <td><?php echo $itemData['depth']; ?></td>
            <td><?php if ($itemData['open'] !== null) { echo $itemData['open'] ? '-' : '+'; } ?></td>
            <td><?php echo str_repeat('&nbsp;', $itemData['depth'] * 4) . htmlspecialchars($itemData['title']); ?></td>
            <td><?php echo htmlspecialchars($itemData['destinationOrAction']); ?></td>
        </tr>
    <?php endforeach; ?>
</table>
