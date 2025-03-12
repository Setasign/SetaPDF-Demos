<?php

namespace setasign\SetaPDF2\Demos\Inspector;

use setasign\SetaPDF2\Demos\ContentStreamProcessor\ColorProcessor;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfStream;

/**
 * Class ColorInspector
 */
class ColorInspector
{
    /**
     * @var Document
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
     * @param Document $document
     */
    public function __construct(Document $document)
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
                    if ($object instanceof PdfStream) {
                        $streamProcessor = new ColorProcessor($annotation->getAppearance($type)->getCanvas(), $this);
                        $streamProcessor->process();

                    } elseif ($object instanceof PdfDictionary) {
                        foreach ($object AS $subType => $subValue) {
                            $subObject = $subValue->ensure();
                            if ($subObject instanceof PdfStream) {
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
     * @param $data
     * @param $info
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
