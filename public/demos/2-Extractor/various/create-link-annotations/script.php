<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\BorderStyle;
use setasign\SetaPDF2\Core\Document\Page\Annotation\LinkAnnotation;
use setasign\SetaPDF2\Core\Exception as CoreException;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Extractor\Extractor;
use setasign\SetaPDF2\Extractor\Result\Collection;
use setasign\SetaPDF2\Extractor\Result\Word;
use setasign\SetaPDF2\Extractor\Result\WordWithGlyphs;
use setasign\SetaPDF2\Extractor\Strategy\Word as WordStrategy;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/tektown/Letterhead.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report.pdf'
];

$file = displayFiles($files);

$writer = new HttpWriter('with-links.pdf', true);
$document = Document::loadByFilename($file, $writer);

$pages = $document->getCatalog()->getPages();

// initiate an extractor instance
$extractor = new Extractor($document);

// define the word strategy and
$strategy = new WordStrategy();
// set the detail level
$strategy->setDetailLevel(WordStrategy::DETAIL_LEVEL_GLYPHS);
// ...pass it to the extractor instance
$extractor->setStrategy($strategy);

// get access to the sorter instance of the strategy
$sorter = $strategy->getSorter();

/**
 * Proxy method to itemsJoining() method of the sorter class.
 *
 * @param WordWithGlyphs $left
 * @param WordWithGlyphs $right
 * @return bool
 * @throws CoreException
 */
$wordsJoining = function(WordWithGlyphs $left, WordWithGlyphs $right) use ($sorter) {
    return $sorter->itemsJoining(
        $left->getGlyphs()[count($left->getGlyphs()) - 1]->getTextItem(),
        $right->getGlyphs()[0]->getTextItem()
    );
};

for ($pageNo = 1; $pageNo <= $pages->count(); $pageNo++) {
    /**
     * @var Word $words[]
     */
    $words = $extractor->getResultByPageNumber($pageNo);

    // get access to the page annotations
    $annotations = $pages->getPage($pageNo)->getAnnotations();

    // let's try to find the links
    /**
     * @var WordWithGlyphs[] $words
     */
    for ($i = 0, $wordCount = count($words); $i < $wordCount; $i++) {
        $word = $words[$i];

        switch (strtolower($word->getString())) {
            case 'www':
            case 'http':
            case 'https':
            case 'ftp':
            case 'sftp':
                $linkItems = [$words[$i]];
                while (isset($words[$i + 1]) && $wordsJoining($words[$i], $words[$i + 1])) {
                    $linkItems[] = $words[++$i];
                }

                // if the link ends with a dot or a comma, left it...
                $lastItemString = $linkItems[count($linkItems) - 1]->getString();
                if (strlen($lastItemString) === 1 && strspn($lastItemString, ',.') === 1) {
                    array_pop($linkItems);
                }

                // get the final link target and do some checks...
                $link = implode('', $linkItems);

                if ($link === 'www' || $link === 'http') {
                    break;
                }

                $url = parse_url($link);
                if ($url === false) {
                    break;
                }

                /** @var array $url */
                if (!isset($url['scheme'])) {
                    $link = 'http://' . $link;
                }

                $link = filter_var($link, FILTER_VALIDATE_URL);
                if ($link === false) {
                    break;
                }

                // we have a link, now get the bounds of it and...
                $linkItems = new Collection($linkItems);
                $bounds = $linkItems->getBounds();
                $ll = $bounds[0]->getLl();
                $ur = $bounds[0]->getUr();

                // ...add a link annotation
                $annotation = new LinkAnnotation(
                    [$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()],
                    $link
                );

                // add a border, to show the link
                $annotation->setColor([1, 0, 0]);
                $annotation->getBorderStyle()
                    ->setWidth(1)
                    ->setStyle(BorderStyle::DASHED)
                    ->setDashPattern([2, 2]);

                $annotations->add($annotation);
                break;

            // check for an email
            case '@':
                $emailItems = [];
                // get the left part before the @-sign
                $a = $i;
                while (isset($words[$a - 1]) && $wordsJoining($words[$a - 1], $words[$a])) {
                    $emailItems[] = $words[--$a];
                }

                // re-order
                $emailItems = array_reverse($emailItems);
                $emailItems[] = $words[$i];

                // get the right part after the @-sign
                while (isset($words[$i + 1]) && $wordsJoining($words[$i], $words[$i + 1])) {
                    $emailItems[] = $words[++$i];
                }

                // if the email address ends with a dot or a comma, left it...
                $lastItemString = $emailItems[count($emailItems) - 1]->getString();
                if (strlen($lastItemString) === 1 && strspn($lastItemString, ',.') === 1) {
                    array_pop($emailItems);
                }

                // get the final email and do some checks...
                $email = implode('', $emailItems);

                $email = filter_var($email, FILTER_VALIDATE_EMAIL);
                if ($email === false) {
                    break;
                }

                // we have a valid email address, so get the bounds and...
                $emailItems = new Collection($emailItems);
                $bounds = $emailItems->getBounds();
                $ll = $bounds[0]->getLl();
                $ur = $bounds[0]->getUr();

                // ...add a link annotation
                $annotation = new LinkAnnotation(
                    [$ll->getX(), $ll->getY(), $ur->getX(), $ur->getY()],
                    'mailto:' . $email
                );

                // add a border, to show the link
                $annotation->setColor([0, 1, 0]);
                $annotation->getBorderStyle()
                    ->setWidth(1)
                    ->setStyle(BorderStyle::DASHED)
                    ->setDashPattern([2, 2]);

                $annotations->add($annotation);
                break;
        }
    }
}

// save and finish
$document->save()->finish();
