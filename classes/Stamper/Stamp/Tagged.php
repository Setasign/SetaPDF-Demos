<?php

namespace setasign\SetaPDF2\Demos\Stamper\Stamp;

use setasign\SetaPDF2\Core\DataStructure\Tree\NumberTree;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Action\Action;
use setasign\SetaPDF2\Core\Document\OptionalContent\Group;
use setasign\SetaPDF2\Core\Document\Page;
use setasign\SetaPDF2\Core\Encoding\Encoding;
use setasign\SetaPDF2\Core\Type\PdfArray;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfName;
use setasign\SetaPDF2\Core\Type\PdfNumeric;
use setasign\SetaPDF2\Core\Type\PdfString;
use setasign\SetaPDF2\Stamper\Stamp\AbstractStamp;

/**
 * Class Tagged
 */
class Tagged extends AbstractStamp
{
    /**
     * @var AbstractStamp
     */
    protected $_mainStamp;

    protected $_tagName = 'Span';
    protected $_title = '';
    protected $_actualText = '';
    protected $_alternateText = '';
    protected $_language = '';

    /**
     * The constructor
     *
     * @param AbstractStamp $mainStamp The main stamp instance
     */
    public function __construct(AbstractStamp $mainStamp)
    {
        $this->_mainStamp = $mainStamp;
    }

    /**
     * @param string $tagName
     */
    public function setTagName(string $tagName)
    {
        $this->_tagName = $tagName;
    }

    /**
     * @param string $title Title in UTF-8
     */
    public function setTitle(string $title)
    {
        $this->_title = $title;
    }

    /**
     * @param string $actualText Actual text in UTF-8
     */
    public function setActualText(string $actualText)
    {
        $this->_actualText = $actualText;
    }

    /**
     * @param string $alternateText Alternate text in UTF-8
     */
    public function setAlternateText(string $alternateText)
    {
        $this->_alternateText = $alternateText;
    }

    /**
     * @param string $language Language in UTF-8
     */
    public function setLanguage(string $language)
    {
        $this->_language = $language;
    }

    /**
     * @inheritDoc
     */
    public function stamp(Document $document, Page $page, array $stampData)
    {
        $this->_mainStamp->_preStamp($document, $page, $stampData);
        $this->_stamp($document, $page, $stampData);
        $quadPoints = $this->_mainStamp->_postStamp($document, $page, $stampData);

        if ($quadPoints !== null) {
            $this->_mainStamp->_putAction(
                $document, $page, $stampData, $quadPoints[0], $quadPoints[1], $quadPoints[2], $quadPoints[3]
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function _stamp(Document $document, Page $page, array $stampData)
    {
        $document->getCatalog()->getMarkInfo()->setMarked(true);

        $structTreeRoot = $document->getCatalog()->getStructTreeRoot();
        $structTreeRoot->getDictionary(true);

        $pageDict = $page->getObject()->ensure();
        if (!$pageDict->offsetExists('StructParents')) {
            $pageDict->offsetSet(
                'StructParents',
                new PdfNumeric($structTreeRoot->getAndIncrementParentTreeNextKey())
            );
        }

        $structParentsKey = $pageDict->getValue('StructParents')->getValue();

        /** @var NumberTree $parentTree */
        $parentTree = $structTreeRoot->getParentTree(true);
        $elements = $parentTree->get($structParentsKey);
        if ($elements !== false) {
            $elements = $elements->ensure();
        } else {
            $elements = new PdfArray();
            $parentTree->add($structParentsKey, $document->createNewObject($elements));
        }

        $mcid = count($elements);

        $element = new PdfDictionary([
            'K' => new PdfNumeric($mcid),
            'P' => $structTreeRoot->getObject(),
            'Pg' => $page->getObject(),
            'S' => new PdfName($this->_tagName, true)
        ]);

        if ($this->_title !== '') {
            $element->offsetSet('T', new PdfString(
                Encoding::toPdfString($this->_title)
            ));
        }

        if ($this->_alternateText !== '') {
            $element->offsetSet('Alt', new PdfString(
                Encoding::toPdfString($this->_alternateText)
            ));
        }

        if ($this->_actualText !== '') {
            $element->offsetSet('ActualText', new PdfString(
                Encoding::toPdfString($this->_actualText)
            ));
        }

        if ($this->_language !== '') {
            $element->offsetSet('Lang', new PdfString(
                Encoding::toPdfString($this->_language)
            ));
        }

        $elementReference = $document->createNewObject($element);

        $elements[] = $elementReference;

        $structTreeRoot->addChild($elementReference);

        $canvas = $page->getCanvas();

        $properties = new PdfDictionary([
            'MCID' => new PdfNumeric($mcid)
        ]);
        $canvas->markedContent()->begin($this->_tagName, $properties);

        $this->_mainStamp->_stamp($document, $page, $stampData);

        $canvas->markedContent()->end();

        return true;
    }

  /* Proxy all standard methods of the main stamp instance */

    /**
     * @inheritDoc
     */
    public function getHeight()
    {
        return $this->_mainStamp->getHeight();
    }

    /**
     * @inheritDoc
     */
    public function getWidth()
    {
        return $this->_mainStamp->getWidth();
    }

    /**
     * @inheritDoc
     */
    public function setOpacity($alpha, $blendMode = 'Normal')
    {
        $this->_mainStamp->setOpacity($alpha, $blendMode);
    }

    /**
     * @inheritDoc
     */
    public function getOpacity()
    {
        return $this->_mainStamp->getOpacity();
    }

    /**
     * @inheritDoc
     */
    public function getOpacityBlendMode()
    {
        return $this->_mainStamp->getOpacityBlendMode();
    }

    /**
     * @inheritDoc
     */
    public function setVisibility($visibility)
    {
        $this->_mainStamp->setVisibility($visibility);
    }

    /**
     * @inheritDoc
     */
    public function getVisibility()
    {
        $this->_mainStamp->getVisibility();
    }

    /**
     * @inheritDoc
     */
    public function setAction(Action $action)
    {
        $this->_mainStamp->setAction($action);
    }

    /**
     * @inheritDoc
     */
    public function getAction()
    {
        return $this->_mainStamp->getAction();
    }

    /**
     * @inheritDoc
     */
    public function setLink($uri)
    {
        $this->_mainStamp->setLink($uri);
    }

    /**
     * @inheritDoc
     */
    public function setOptionalContentGroup(?Group $optionalContentGroup = null)
    {
        $this->_mainStamp->setOptionalContentGroup($optionalContentGroup);
    }

    /**
     * @inheritDoc
     */
    public function getOptionalContentGroup()
    {
        return $this->_mainStamp->getOptionalContentGroup();
    }

    /**
     * @inheritDoc
     */
    protected function _getOpacityGraphicState(Document $document, $opacity)
    {
        return $this->_mainStamp->_getOpacityGraphicState($document, $opacity);
    }

    /**
     * @inheritDoc
     */
    protected function _getVisibilityGroup(Document $document)
    {
        return $this->_mainStamp->_getVisibilityGroup($document);
    }

    /**
     * @inheritDoc
     */
    protected function _ensureResources(Document $document, Page $page)
    {
        return $this->_mainStamp->_ensureResources($document, $page);
    }
}
