<?php

namespace com\setasign\SetaPDF\Demos\Signer\Appearance;

use SetaPDF_Core_Document;
use SetaPDF_Signer;
use SetaPDF_Signer_SignatureField;

class OnAllPages extends \SetaPDF_Signer_Signature_Appearance_AbstractAppearance
{
    /**
     * @var \SetaPDF_Core_XObject_Form
     */
    protected $_formXObject;

    /**
     * @var \SetaPDF_Signer_Signature_Appearance_AbstractAppearance
     */
    protected $_mainAppearance;

    /**
     * @param \SetaPDF_Signer_Signature_Appearance_AbstractAppearance $mainAppearance
     */
    public function __construct(\SetaPDF_Signer_Signature_Appearance_AbstractAppearance $mainAppearance)
    {
        $this->_mainAppearance = $mainAppearance;
    }

    /**
     * Proxy method to the main appearance instance.
     *
     * Internally it adds stamp annotations to all other pages on the same position with the same appearance.
     *
     * @param \SetaPDF_Signer_SignatureField $field
     * @param \SetaPDF_Core_Document $document
     * @param \SetaPDF_Signer $signer
     */
    public function createAppearance(
        \SetaPDF_Signer_SignatureField $field,
        \SetaPDF_Core_Document $document,
        \SetaPDF_Signer $signer
    ) {
        $this->_mainAppearance->createAppearance($field, $document, $signer);

        $pages = $document->getCatalog()->getPages();
        $pageOfRealSignature = $pages->getPageByAnnotation($field);

        for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
            $page = $pages->getPage($pageNo);

            if ($page === $pageOfRealSignature) {
                continue;
            }

            $annotation = new \SetaPDF_Core_Document_Page_Annotation_Stamp($field->getRect());
            $annotation->setName(\uniqid('', true));
            $annotation->setModificationDate(new \DateTime());
            $annotation->setPrintFlag();
            $annotation->setLocked();
            $annotation->setLockedContents();
            $annotation->setSubject(\sprintf(
                'Copy of signature appearance of signature field "%s"',
                \SetaPDF_Core_Encoding::convertPdfString($field->getQualifiedName())
            ));
            $annotation->setTextLabel($signer->getName());

            $annotation->setAppearance($this->_getFormXObject($field, $document, $signer));
            $page->getAnnotations()->add($annotation);
        }
    }

    /**
     * Get a reusable form XObject.
     *
     * @param \SetaPDF_Signer_SignatureField $field
     * @param \SetaPDF_Core_Document $document
     * @param \SetaPDF_Signer $signer
     * @return \SetaPDF_Core_XObject_Form
     */
    protected function _getFormXObject(
        \SetaPDF_Signer_SignatureField $field,
        \SetaPDF_Core_Document $document,
        \SetaPDF_Signer $signer
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
     * @param SetaPDF_Signer_SignatureField $field
     * @param SetaPDF_Core_Document $document
     * @param SetaPDF_Signer $signer
     * @return \SetaPDF_Core_XObject_Form
     */
    protected function _getN2XObject(
        SetaPDF_Signer_SignatureField $field,
        SetaPDF_Core_Document $document,
        SetaPDF_Signer $signer
    ) {
        return $this->_mainAppearance->_getN2XObject($field, $document, $signer);
    }
}
