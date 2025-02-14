<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Word;
use setasign\SetaPDF2\Extractor\Strategy\Word as WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/lenstown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

$path = displayFiles($files);

$document = Document::loadByFilename($path);
$extractor = new Extractor($document);

$strategy = new WordStrategy();
$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $words = $extractor->getResultByPageNumber($pageNo);
    echo '<b>There are ' . count($words) . ' words found on Page #' . $pageNo . ':</b><br/>';

    echo '<table border="1" width="100%">';
    echo '<tr><th>Word</th><th>llx</th><th>lly</th><th>urx</th><th>ury</th></tr>';

    /** @var Word $word */
    foreach ($words as $word) {
        $bounds = $word->getBounds()[0];
        printf(
            '<tr><td>&quot%s&quot</td><td>%.3F</td><td>%.3F</td><td>%.3F</td><td>%.3F</td></tr>',
            htmlspecialchars($word->getString()),
            $bounds->getLl()->getX(),
            $bounds->getLl()->getY(),
            $bounds->getUr()->getX(),
            $bounds->getUr()->getY()
        );
    }

    echo '</table><br/><br/>';
}
