<?php

namespace setasign\SetaPDF2\Demos\Signer\Appearance;

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page\Annotation\StampAnnotation;
use setasign\SetaPDF2\Core\Encoding\Encoding;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\Signer\Signature\Appearance\AbstractAppearance;
use setasign\SetaPDF2\Signer\Signer;
use setasign\SetaPDF2\Signer\SignatureField;

class OnAllPages extends AbstractAppearance
{
    /**
     * @var Form
     */
    protected $_formXObject;

    /**
     * @var AbstractAppearance
     */
    protected $_mainAppearance;

    /**
     * @param AbstractAppearance $mainAppearance
     */
    public function __construct(AbstractAppearance $mainAppearance)
    {
        $this->_mainAppearance = $mainAppearance;
    }

    /**
     * Proxy method to the main appearance instance.
     *
     * Internally it adds stamp annotations to all other pages on the same position with the same appearance.
     *
     * @param SignatureField $field
     * @param Document $document
     * @param Signer $signer
     */
    public function createAppearance(
        SignatureField $field,
        Document $document,
        Signer $signer
    ) {
        $this->_mainAppearance->createAppearance($field, $document, $signer);

        $pages = $document->getCatalog()->getPages();
        $pageOfRealSignature = $pages->getPageByAnnotation($field);

        for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
            $page = $pages->getPage($pageNo);

            if ($page === $pageOfRealSignature) {
                continue;
            }

            $annotation = new StampAnnotation($field->getRect());
            $annotation->setName(\uniqid('', true));
            $annotation->setModificationDate(new \DateTime());
            $annotation->setPrintFlag();
            $annotation->setLocked();
            $annotation->setLockedContents();
            $annotation->setSubject(\sprintf(
                'Copy of signature appearance of signature field "%s"',
                Encoding::convertPdfString($field->getQualifiedName())
            ));
            $annotation->setTextLabel($signer->getName());

            $annotation->setAppearance($this->_getFormXObject($field, $document, $signer));
            $page->getAnnotations()->add($annotation);
        }
    }

    /**
     * Get a reusable form XObject.
     *
     * @param SignatureField $field
     * @param Document $document
     * @param Signer $signer
     * @return Form
     */
    protected function _getFormXObject(
        SignatureField $field,
        Document $document,
        Signer $signer
    ) {
        if ($this->_formXObject === null) {
            $this->_formXObject = $this->_mainAppearance->_getN2XObject($field, $document, $signer);

            $matrix = $field->getAppearance()->getIndirectObject()->ensure()->getValue()->getValue('Matrix');
            if ($matrix) {
                $this->_formXObject->setMatrix($matrix->toPhp(true));
            }
        }

        return $this->_formXObject;
    }

    /**
     * @param SignatureField $field
     * @param Document $document
     * @param Signer $signer
     * @return Form
     */
    protected function _getN2XObject(
        SignatureField $field,
        Document $document,
        Signer $signer
    ) {
        return $this->_mainAppearance->_getN2XObject($field, $document, $signer);
    }
}
