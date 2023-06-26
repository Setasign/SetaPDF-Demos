<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    'Laboratory-Report.pdf' => $assetsDirectory . '/pdfs/tektown/Laboratory-Report.pdf',
    'Fact-Sheet.pdf' => $assetsDirectory . '/pdfs/tektown/Fact-Sheet.pdf',
    'Terms-and-Conditions.pdf' => $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
];
$dpi = 72;

if (isset($_GET['action']) && $_GET['action'] === 'preview') {
    // download the pdf file
    if (!array_key_exists($_GET['file'], $files)) {
        throw new Exception('Invalid file!');
    }
    $file = $files[$_GET['file']];

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; preview.pdf');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Accept-Ranges: none');
    $content = file_get_contents($file);
    header('Content-Length: ' . strlen($content));
    echo $content;
    return;

} elseif (isset($_GET['action']) && $_GET['action'] === 'generateImagePreview') {
    // generate the preview image of the pdf
    if (!array_key_exists($_GET['file'], $files)) {
        throw new Exception('Invalid file!');
    }
    $file = $files[$_GET['file']];
    $pageNo = isset($_GET['page']) ? $_GET['page'] : 1;
    $imageFile = 'images/' . basename($file, '.pdf') . '-' . $dpi . '-PAGE.png';
    $realImageFile = str_replace('PAGE', $pageNo, $imageFile);


    if (!file_exists($realImageFile)) {
        $cmd = 'mutool draw -F png -r ' . escapeshellarg($dpi) . ' -o ' . str_replace('PAGE', '%d', escapeshellarg($imageFile))
            . ' ' . escapeshellarg($file) . ' ' . escapeshellarg($pageNo);

        exec($cmd, $output, $resultCode);

        if ($resultCode !== 0) {
            echo 'Thumbnail could not be generated. Please make sure that ' .
                '<a href="https://www.mupdf.com/docs/manual-mutool-draw.html" target="_blank">mutool</a> is installed ' .
                'and that the images/ folder is writable.';
            die();
        }
    }

    header('Content-Type: image/png');
    header('Content-Disposition: inline; image.png');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Accept-Ranges: none');
    $content = file_get_contents($realImageFile);
    header('Content-Length: ' . strlen($content));
    echo $content;
    return;

} elseif (isset($_GET['action']) && $_GET['action'] === 'fetchPageCountAndFormats') {
    // fetch the page count and the page size
    if (!array_key_exists($_GET['file'], $files)) {
        throw new Exception('Invalid file!');
    }
    $file = $files[$_GET['file']];

    $document = \SetaPDF_Core_Document::loadByFilename($file);
    $pages = $document->getCatalog()->getPages();
    $pageCount = $pages->count();
    $pageFormats = [];
    for ($i = 1; $i <= $pageCount; $i++) {
        $page = $pages->getPage($i);
        list($width, $height) = $page->getWidthAndHeight();
        $pageFormats[] = [$width, $height];
    }
    if ($pageCount === 0) {
        throw new Exception('PDF is empty');
    }

    header('Content-Type: application/json');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Accept-Ranges: none');
    $content = json_encode([
        'pageCount' => $pageCount,
        'pageFormats' => $pageFormats,
    ]);
    header('Content-Length: ' . strlen($content));
    echo $content;
    return;

} elseif (isset($_GET['action']) && $_GET['action'] === 'extract') {
    // extract text by selected locations
    if (!array_key_exists($_GET['file'], $files)) {
        throw new Exception('Invalid file!');
    }
    $file = $files[$_GET['file']];

    $page = $_GET['page'];
    // upper left point
    $x1 = $_GET['data']['x1'];
    $y1 = $_GET['data']['y1'];
    // lower right point
    $x2 = $_GET['data']['x2'];
    $y2 = $_GET['data']['y2'];

    // load the document
    $document = \SetaPDF_Core_Document::loadByFilename($file);

    // get access to its pages
    $pages = $document->getCatalog()->getPages();

    // the interresting part: initiate an extractor instance
    $extractor = new \SetaPDF_Extractor($document);

    // create a word strategy instance
    $strategy = new \SetaPDF_Extractor_Strategy_ExactPlain();
    // pass a rectangle filter to the strategy
    $strategy->setFilter(new \SetaPDF_Extractor_Filter_Rectangle(
        new \SetaPDF_Core_Geometry_Rectangle($x1, $y1, $x2, $y2),
        \SetaPDF_Extractor_Filter_Rectangle::MODE_CONTACT
    ));
    $extractor->setStrategy($strategy);

    // get the text of a page
    $result = $extractor->getResultByPageNumber($page);

    header('Content-Type: application/json');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Accept-Ranges: none');
    $content = json_encode([
        'result' => htmlspecialchars($result),
    ]);
    header('Content-Length: ' . strlen($content));
    echo $content;
    return;
} else {
    $filePath = displayFiles($files);
    $file = array_search($filePath, $files);
    if ($file === false) {
        throw new Exception('Invalid file selected');
    }
    require './gui.php';
}