<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Terms-and-Conditions.pdf',
    $assetsDirectory . '/pdfs/etown/Laboratory-Report.pdf',
    $assetsDirectory . '/pdfs/lenstown/Fact-Sheet.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
];

displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($_GET['f']);
$extractor = new SetaPDF_Extractor($document);

$strategy = new SetaPDF_Extractor_Strategy_Glyph();
$extractor->setStrategy($strategy);

$pageCount = $document->getCatalog()->getPages()->count();

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $glyphs = $extractor->getResultByPageNumber($pageNo);
    echo '<b>There are ' . count($glyphs) . ' glyphs found on Page #' . $pageNo . ':</b><br/><pre>';

    /** @var SetaPDF_Extractor_Result_Glyph $glyph */
    foreach ($glyphs as $glyph) {
        $bounds = $glyph->getBounds()[0];
        printf(
            '  Glyph: "%s" [llx: %.3F, lly: %.3F, urx: %.3F, ury: %.3F]<br/>',
            htmlspecialchars($glyph->getString()),
            $bounds->getLl()->getX(),
            $bounds->getLl()->getY(),
            $bounds->getUr()->getX(),
            $bounds->getUr()->getY()
        );
    }

    echo '</pre><br/><br/>';
}
