<?php

namespace com\setasign\SetaPDF\Demos\Stamper\Stamp;

/**
 * Class ArtifactTextStamp
 */
class ArtifactTextStamp extends \SetaPDF_Stamper_Stamp_Text
{
    /**
     * @inheritDoc
     */
    protected function _preStamp(\SetaPDF_Core_Document $document, \SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $page->getCanvas()
            ->markedContent()
            ->begin('Artifact');

        parent::_preStamp($document, $page, $stampData);
    }

    /**
     * @inheritDoc
     */
    protected function _postStamp(\SetaPDF_Core_Document $document, \SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $quadPoints = parent::_postStamp($document, $page, $stampData);

        $page->getCanvas()
            ->markedContent()
            ->end();

        return $quadPoints;
    }
}
