<?php

namespace setasign\SetaPDF2\Demos\Inspector;

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Resource\ResourceInterface;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\XObject\XObject;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\Core\XObject\Image;
use setasign\SetaPDF2\Exception\NotImplemented;

/**
 * Class TransparencyInspector
 */
class TransparencyInspector
{
    /**
     * @var Document
     */
    protected $_document;

    /**
     * Information about the currently processed "location"
     *
     * @var string
     */
    protected $_currentLocation = [];

    /**
     * Found elements
     *
     * @var array
     */
    protected $_elements = [];

    /**
     * The constructor
     *
     * @param Document $document
     */
    public function __construct(Document $document)
    {
        $this->_document = $document;
    }

    /**
     * Get elements that invoke transparency behavior in the document.
     *
     * @return array
     */
    public function process()
    {
        $this->_elements = [];
        $pages = $this->_document->getCatalog()->getPages();

        for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
            $page = $pages->getPage($pageNo);

            $this->_currentLocation = ['Page ' . $pageNo];

            $xObjects = $page->getCanvas()->getResources(true, false, ResourceInterface::TYPE_X_OBJECT);
            if ($xObjects) {
                $this->_processXObjects($xObjects);
            }

            $graphicStates = $page->getCanvas()->getResources(true, false, ResourceInterface::TYPE_EXT_G_STATE);
            if ($graphicStates) {
                $this->_processGraphicStates($graphicStates);
            }
        }

        return $this->_elements;
    }

    /**
     * Check graphic states for transparency.
     *
     * @param PdfDictionary $graphicStates
     */
    protected function _processGraphicStates(PdfDictionary $graphicStates)
    {
        $root = $this->_currentLocation;
        foreach ($graphicStates AS $name => $graphicState) {
            $this->_currentLocation = $root;
            $this->_currentLocation[] = 'GraphicState (' . $name . ')';

            $dictionary = $graphicState->ensure();
            if (!$dictionary instanceof PdfDictionary) {
                continue;
            }

            if (isset($dictionary['SMask']) && $dictionary->getValue('SMask')->ensure()->getValue() !== 'None') {
                $this->_addTransparentElement('GraphicState', $dictionary, 'Graphic state with SMask entry');
                continue;
            }

            if (isset($dictionary['CA']) && $dictionary->getValue('CA')->getValue() != 1.0) {
                $this->_addTransparentElement(
                    'GraphicState',
                    $dictionary,
                    'Graphic state with "CA" value of ' . sprintf('%.5F', $dictionary->getValue('CA')->getValue())
                );
            }

            if (isset($dictionary['ca']) && $dictionary->getValue('ca')->getValue() != 1.0) {
                $this->_addTransparentElement(
                    'GraphicState',
                    $dictionary,
                    'Graphic state with "ca" value of ' . sprintf('%.5F', $dictionary->getValue('CA')->getValue())
                );
            }
        }

        $this->_currentLocation = $root;
    }

    /**
     * Check XObjects for transparency.
     *
     * @param PdfDictionary $xObjects
     * @throws NotImplemented
     */
    protected function _processXObjects(PdfDictionary $xObjects)
    {
        $root = $this->_currentLocation;
        foreach ($xObjects AS $name => $xObject) {
            $this->_currentLocation = $root;
            $this->_currentLocation[] = 'XObject (' . $name . ')';

            $xObject = XObject::get($xObject);

            // images
            if ($xObject instanceof Image) {
                $dictionary = $xObject->getIndirectObject()->ensure()->getValue();

                /* An image XObject may contain its own soft-mask image in the form of a subsidiary image XObject in the
                 * SMask entry of the image dictionary (see “Image Dictionaries”). This mask, if present, shall override
                 * any explicit or colour key mask specified by the image dictionary’s Mask entry. Either form of mask
                 * in the image dictionary shall override the current soft mask in the graphics state.
                 */
                if (isset($dictionary['SMask'])) {
                    $this->_addTransparentElement('Image', $xObject, 'Image with SMask entry');
                    continue;
                }

                /* An image XObject that has a JPXDecode filter as its data source may specify an SMaskInData entry,
                 * indicating that the soft mask is embedded in the data stream (see “JPXDecode Filter”).
                 */
                if ($dictionary->getValue('Filter')->getValue() === 'JPXDecode') {
                    if (isset($dictionary['SMaskInData']) && $dictionary->getValue('SMaskInData')->getValue() != 0) {
                        $this->_addTransparentElement(
                            'Image',
                            $xObject,
                            'Image with JPXDecode filter and SMaskInData entry'
                        );
                        continue;
                    }
                }

            // form XObjects
            } elseif ($xObject instanceof Form) {
                $_xObjects = $xObject->getCanvas()->getResources(true, false, ResourceInterface::TYPE_X_OBJECT);
                if ($_xObjects) {
                    $this->_processXObjects($_xObjects);
                }

                $graphicStates = $xObject->getCanvas()->getResources(true, false, ResourceInterface::TYPE_EXT_G_STATE);
                if ($graphicStates) {
                    $this->_processGraphicStates($graphicStates);
                }
            }
        }
    }

    protected function _addTransparentElement($type, $data, $info)
    {
        $this->_elements[] = [
            'type' => $type,
            'data' => $data,
            'info' => $info,
            'location' => join(', ', $this->_currentLocation)
        ];
    }
}