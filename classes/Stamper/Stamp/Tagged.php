<?php

namespace com\setasign\SetaPDF\Demos\Stamper\Stamp;

/**
 * Class Tagged
 */
class Tagged extends \SetaPDF_Stamper_Stamp
{
    /**
     * @var \SetaPDF_Stamper_Stamp
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
     * @param \SetaPDF_Stamper_Stamp $mainStamp The main stamp instance
     */
    public function __construct(\SetaPDF_Stamper_Stamp $mainStamp)
    {
        $this->_mainStamp = $mainStamp;
    }

    /**
     * @param string $tagName
     */
    public function setTagName($tagName)
    {
        $this->_tagName = $tagName;
    }

    /**
     * @param string $title Title in UTF-8
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @param string $actualText Actual text in UTF-8
     */
    public function setActualText($actualText)
    {
        $this->_actualText = $actualText;
    }

    /**
     * @param string $alternateText Alternate text in UTF-8
     */
    public function setAlternateText($alternateText)
    {
        $this->_alternateText = $alternateText;
    }

    /**
     * @param string $language Language in UTF-8
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    /**
     * @inheritDoc
     */
    public function stamp(\SetaPDF_Core_Document $document, \SetaPDF_Core_Document_Page $page, array $stampData)
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
    protected function _stamp(\SetaPDF_Core_Document $document, \SetaPDF_Core_Document_Page $page, array $stampData)
    {
        $document->getCatalog()->getMarkInfo()->setMarked(true);

        $structTreeRoot = $document->getCatalog()->getStructTreeRoot();
        $structTreeRoot->getDictionary(true);

        $pageDict = $page->getObject()->ensure();
        if (!$pageDict->offsetExists('StructParents')) {
            $pageDict->offsetSet(
                'StructParents',
                new \SetaPDF_Core_Type_Numeric($structTreeRoot->getAndIncrementParentTreeNextKey())
            );
        }

        $structParentsKey = $pageDict->getValue('StructParents')->getValue();

        /** @var \SetaPDF_Core_DataStructure_NumberTree $parentTree */
        $parentTree = $structTreeRoot->getParentTree(true);
        $elements = $parentTree->get($structParentsKey);
        if ($elements !== false) {
            $elements = $elements->ensure();
        } else {
            $elements = new \SetaPDF_Core_Type_Array();
            $parentTree->add($structParentsKey, $document->createNewObject($elements));
        }

        $mcid = count($elements);

        $element = new \SetaPDF_Core_Type_Dictionary([
            'K' => new \SetaPDF_Core_Type_Numeric($mcid),
            'P' => $structTreeRoot->getObject(),
            'Pg' => $page->getObject(),
            'S' => new \SetaPDF_Core_Type_Name($this->_tagName, true)
        ]);

        if ($this->_title !== '') {
            $element->offsetSet('T', new \SetaPDF_Core_Type_String(
                \SetaPDF_Core_Encoding::toPdfString($this->_title)
            ));
        }

        if ($this->_alternateText !== '') {
            $element->offsetSet('Alt', new \SetaPDF_Core_Type_String(
                \SetaPDF_Core_Encoding::toPdfString($this->_alternateText)
            ));
        }

        if ($this->_actualText !== '') {
            $element->offsetSet('ActualText', new \SetaPDF_Core_Type_String(
                \SetaPDF_Core_Encoding::toPdfString($this->_actualText)
            ));
        }

        if ($this->_language !== '') {
            $element->offsetSet('Lang', new \SetaPDF_Core_Type_String(
                \SetaPDF_Core_Encoding::toPdfString($this->_language)
            ));
        }

        $elementReference = $document->createNewObject($element);

        $elements[] = $elementReference;

        $structTreeRoot->addChild($elementReference);

        $canvas = $page->getCanvas();

        $properties = new \SetaPDF_Core_Type_Dictionary([
            'MCID' => new \SetaPDF_Core_Type_Numeric($mcid)
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
    public function setAction(\SetaPDF_Core_Document_Action $action)
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
    public function setOptionalContentGroup(\SetaPDF_Core_Document_OptionalContent_Group $optionalContentGroup = null)
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
    protected function _getOpacityGraphicState(\SetaPDF_Core_Document $document, $opacity)
    {
        return $this->_mainStamp->_getOpacityGraphicState($document, $opacity);
    }

    /**
     * @inheritDoc
     */
    protected function _getVisibilityGroup(\SetaPDF_Core_Document $document)
    {
        return $this->_mainStamp->_getVisibilityGroup($document);
    }

    /**
     * @inheritDoc
     */
    protected function _ensureResources(\SetaPDF_Core_Document $document, \SetaPDF_Core_Document_Page $page)
    {
        return $this->_mainStamp->_ensureResources($document, $page);
    }
}
