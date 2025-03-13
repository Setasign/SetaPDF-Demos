<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\GoToAction;
use setasign\SetaPDF2\Core\Document\Destination;
use setasign\SetaPDF2\Core\Document\Page\Annotation\LinkAnnotation;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Collection;
use setasign\SetaPDF2\Extractor\Result\Word;
use setasign\SetaPDF2\Extractor\Strategy\Word as WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/Brand-Guide-without-links.pdf',
    new HttpWriter('document.pdf', true)
);

$extractor = new Extractor($document);

$strategy = new WordStrategy();
$extractor->setStrategy($strategy);

$lines = [];

$tocStartPage = 2;
$tocEndPage = 2;
$offset = 2;

$pages = $document->getCatalog()->getPages();

for ($pageNo = $tocStartPage; $pageNo <= $tocEndPage; $pageNo++) {
    /**
     * @var Word[] $words
     */
    $words = $extractor->getResultByPageNumber($pageNo);

    /**
     * @var $lines Collection[][]
     */
    $lines[$pageNo] = [];
    $line = new Collection();

    /**
     * @var Word $prevWord
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
                $line = new Collection();
            }
        }

        $line[] = $word;
        $prevWord = $word;
    }

    if (count($line) > 0) {
        $lines[$pageNo][] = $line;
        $line = new Collection();
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

        $action = new GoToAction(Destination::createByPage($pages->getPage($linkToPageNo + $offset)));
        $bounds = $line->getBounds();
        $ll = $bounds[0]->getLl();
        $ur = $bounds[0]->getUr();
        $annotation = new LinkAnnotation(
            [$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()],
            $action
        );
        $annotations->add($annotation);
    }
}

$document->save()->finish();
