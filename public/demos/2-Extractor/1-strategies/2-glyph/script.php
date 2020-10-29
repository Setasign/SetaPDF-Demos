<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/lenstown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

$path = displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($path);
$extractor = new SetaPDF_Extractor($document);

$strategy = new SetaPDF_Extractor_Strategy_Glyph();
$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $glyphs = $extractor->getResultByPageNumber($pageNo);
    echo '<b>There are ' . count($glyphs) . ' glyphs found on Page #' . $pageNo . ':</b><br/>';

    echo '<table border="1" width="100%">';
    echo '<tr><th>Glyph</th><th>llx</th><th>lly</th><th>urx</th><th>ury</th><th>Font Name</th></tr>';

    /** @var SetaPDF_Extractor_Result_Glyph $glyph */
    foreach ($glyphs as $glyph) {
        $bounds = $glyph->getBounds()[0];
        printf(
            '<tr><td>&quot%s&quot</td><td>%.3F</td><td>%.3F</td><td>%.3F</td><td>%.3F</td><td>%s</td></tr>',
            htmlspecialchars($glyph->getString()),
            $bounds->getLl()->getX(),
            $bounds->getLl()->getY(),
            $bounds->getUr()->getX(),
            $bounds->getUr()->getY(),
            htmlspecialchars($glyph->getTextItem()->getFont()->getFontName())
        );
    }

    echo '</table><br/><br/>';
}
