<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = \SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide-without-links.pdf',
    new \SetaPDF_Core_Writer_Http('document.pdf', true)
);

$extractor = new SetaPDF_Extractor($document);

$strategy = new SetaPDF_Extractor_Strategy_Word();
$extractor->setStrategy($strategy);

$lines = [];

$tocStartPage = 2;
$tocEndPage = 2;
$offset = 2;

$pages = $document->getCatalog()->getPages();

for ($pageNo = $tocStartPage; $pageNo <= $tocEndPage; $pageNo++) {
    /**
     * @var SetaPDF_Extractor_Result_Word[] $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);

    /**
     * @var $lines SetaPDF_Extractor_Result_Collection[][]
     */
    $lines[$pageNo] = [];
    $line = new SetaPDF_Extractor_Result_Collection();

    /**
     * @var SetaPDF_Extractor_Result_Word $prevWord
     */
    $prevWord = null;

    foreach ($words AS $word) {
        if ($prevWord) {
            $prevBounds = $prevWord->getBounds();
            $bounds = $word->getBounds();

            $prevY = $prevBounds[0]->getLl()->getY();
            $y = $bounds[0]->getLl()->getY();

            // group by lines
            if (abs($prevY - $y) > 4) {
                $lines[$pageNo][] = $line;
                $line = new SetaPDF_Extractor_Result_Collection();
            }
        }

        $line[] = $word;
        $prevWord = $word;
    }

    if (count($line) > 0) {
        $lines[$pageNo][] = $line;
        $line = new SetaPDF_Extractor_Result_Collection();
    }

    $annotations = $pages->getPage($pageNo)->getAnnotations();

    foreach ($lines[$pageNo] AS $i => $line) {
        // reconstruct line text and get max/min bounds
        $lineText = '';

        $llx = $lly = $urx = $ury = null;
        foreach ($line as $word) {
            $lineText .= $word->getString() . ' ';
        }

        $lineText = trim($lineText);

        // extract target page number
        if (!preg_match("/(\d+)$/u", $lineText, $m)) {
            continue;
        }

        $linkToPageNo = $m[1];

        $action = new \SetaPDF_Core_Document_Action_GoTo(
            \SetaPDF_Core_Document_Destination::createByPage($pages->getPage($linkToPageNo + $offset))
        );
        $bounds = $line->getBounds();
        $ll = $bounds[0]->getLl();
        $ur = $bounds[0]->getUr();
        $annotation = new \SetaPDF_Core_Document_Page_Annotation_Link(
            [$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()],
            $action
        );
        $annotations->add($annotation);
    }
}

$document->save()->finish();
