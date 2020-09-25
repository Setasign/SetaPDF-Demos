<?php

namespace com\setasign\SetaPDF\Demos\ContentStreamProcessor;

/**
 * Class TextProcessor
 */
class TextProcessor
{
    /**
     * The canvas object
     *
     * @var \SetaPDF_Core_Canvas
     */
    protected $_canvas;

    /**
     * @var boolean
     */
    protected $_hasText;

    /**
     * The constructor
     *
     * The parameter are the content stream and its resources dictionary.
     *
     * @param \SetaPDF_Core_Canvas $canvas
     */
    public function __construct(\SetaPDF_Core_Canvas $canvas)
    {
        $this->_canvas = $canvas;
    }

    /**
     * Checks for text on the initially passed canvas instance.
     *
     * Returns true if there is any text in the stream, otherwise false
     *
     * @return bool
     */
    public function hasText()
    {
        // if there are no resources no text can be output because no font is defined
        $resources = $this->_canvas->getResources();
        if ($resources === false) {
            return false;
        }

        $this->_hasText = false;

        $parser = $this->_createContentParser();
        $parser->process();
        $parser->cleanUp();

        return $this->_hasText;
    }

    /**
     * Create a content parser instance.
     *
     * @return \SetaPDF_Core_Parser_Content
     */
    protected function _createContentParser()
    {
        $contentParser = new \SetaPDF_Core_Parser_Content($this->_canvas->getStream());

        // register a callback for text output operators
        $contentParser->registerOperator(
            ['Tj', 'TJ', '"', "'"],
            function () {
                $this->_hasText = true;
                return false;
            }
        );

        // register a callback to handle form XObjects
        $contentParser->registerOperator(
            'Do',
            function ($arguments) {
                $xObjects = $this->_canvas->getResources(true, false, \SetaPDF_Core_Resource::TYPE_X_OBJECT);
                if ($xObjects === false) {
                    return;
                }

                $xObject = $xObjects->getValue($arguments[0]->getValue());
                $xObject = \SetaPDF_Core_XObject::get($xObject);

                if ($xObject instanceof \SetaPDF_Core_XObject_Form) {
                    $processor = new self($xObject->getCanvas());

                    $this->_hasText = $processor->hasText();
                    if ($this->_hasText === true) {
                        return false;
                    }
                }
            }
        );

        return $contentParser;
    }
}