<?php

namespace com\setasign\SetaPDF\Demos\Inspector;

use com\setasign\SetaPDF\Demos\ContentStreamProcessor\ColorProcessor;

/**
 * Class ColorInspector
 */
class ColorInspector
{
    /**
     * @var \SetaPDF_Core_Document
     */
    protected $_document;

    /**
     * All found color definitions
     *
     * @var array
     */
    protected $_colors = [];

    /**
     * Information about the currently processed "location"
     *
     * @var string
     */
    protected $_currentLocation;

    /**
     * The constructor
     *
     * @param \SetaPDF_Core_Document $document
     */
    public function __construct(\SetaPDF_Core_Document $document)
    {
        $this->_document = $document;
    }

    /**
     * Get all used colors
     *
     * @param bool $processAnnotations Set to false to ignore color definitions in annotation appearance streams
     * @param null|integer $maxPages The maximum of pages to process
     * @return array
     */
    public function getColors($processAnnotations = true, $maxPages = null)
    {
        $pages = $this->_document->getCatalog()->getPages();

        $pageCount = $pages->count();
        $maxPages = $maxPages === null ? $pageCount : min($maxPages, $pageCount);

        for ($pageNo = 1; $pageNo <= $maxPages; $pageNo++) {
            $this->_currentLocation = 'Page ' . $pageNo;

            $page = $pages->getPage($pageNo);
            $canvas = $page->getCanvas();
            $streamProcessor = new ColorProcessor($canvas, $this);
            $streamProcessor->process();

            if ($processAnnotations === false) {
                continue;
            }

            $annotations = $page->getAnnotations();
            $allAnnotations = $annotations->getAll();
            foreach ($allAnnotations AS $annotation) {
                $dict = $annotation->getDictionary();
                $ap = $dict->getValue('AP');
                if ($ap === null) {
                    continue;
                }

                $this->_currentLocation = 'Annotation (' . $dict->getValue('Subtype')->getValue() . ') on Page ' . $pageNo;

                foreach ($ap AS $type => $value) {
                    $object = $value->ensure();
                    if ($object instanceof \SetaPDF_Core_Type_Stream) {
                        $streamProcessor = new ColorProcessor($annotation->getAppearance($type)->getCanvas(), $this);
                        $streamProcessor->process();

                    } elseif ($object instanceof \SetaPDF_Core_Type_Dictionary) {
                        foreach ($object AS $subType => $subValue) {
                            $subOject = $subValue->ensure();
                            if ($subOject instanceof \SetaPDF_Core_Type_Stream) {
                                $streamProcessor = new ColorProcessor(
                                    $annotation->getAppearance($type, $subType)->getCanvas(), $this
                                );
                                $streamProcessor->process();
                            }
                        }
                    }
                }
            }
        }

        return $this->_colors;
    }

    /**
     * A method which will register found color definitions.
     *
     * @param $colorSpace
     * @param null $data
     * @param null $info
     */
    public function addFoundColor($colorSpace, $data = null, $info = null)
    {
        $this->_colors[] = [
            'colorSpace' => $colorSpace,
            'data' => $data,
            'info' => $info,
            'location' => $this->_currentLocation,
        ];
    }
}
