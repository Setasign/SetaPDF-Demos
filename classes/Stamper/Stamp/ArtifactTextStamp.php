<?php

namespace setasign\SetaPDF2\Demos\Stamper\Stamp;

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page;
use setasign\SetaPDF2\Stamper\Stamp\Text as TextStamp;

/**
 * Class ArtifactTextStamp
 */
class ArtifactTextStamp extends TextStamp
{
    /**
     * @inheritDoc
     */
    protected function _preStamp(Document $document, Page $page, array $stampData)
    {
        $page->getCanvas()
            ->markedContent()
            ->begin('Artifact');

        parent::_preStamp($document, $page, $stampData);
    }

    /**
     * @inheritDoc
     */
    protected function _postStamp(Document $document, Page $page, array $stampData)
    {
        $quadPoints = parent::_postStamp($document, $page, $stampData);

        $page->getCanvas()
            ->markedContent()
            ->end();

        return $quadPoints;
    }
}
