<?php

namespace setasign\SetaPDF2\Demos\ContentStreamProcessor;

use setasign\SetaPDF2\Core\Canvas\Canvas;
use setasign\SetaPDF2\Core\Filter\Exception as FilterException;
use setasign\SetaPDF2\Core\Parser\Content;
use setasign\SetaPDF2\Core\Resource\ResourceInterface;
use setasign\SetaPDF2\Core\XObject\XObject;
use setasign\SetaPDF2\Core\XObject\Form;

/**
 * Class TextProcessor
 */
class TextProcessor
{
    /**
     * The canvas object
     *
     * @var Canvas
     */
    protected $_canvas;

    /**
     * @var bool
     */
    protected $_hasText;

    /**
     * The constructor
     *
     * The parameter is the canvas instance.
     *
     * @param Canvas $canvas
     */
    public function __construct(Canvas $canvas)
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
    public function hasText(): bool
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
     * @return Content
     */
    protected function _createContentParser()
    {
        try {
            $stream = $this->_canvas->getStream();
        } catch (FilterException $e) {
            // if a stream cannot be unfiltered, we ignore it
            $stream = '';
        }

        $contentParser = new Content($stream);

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
                $xObjects = $this->_canvas->getResources(true, false, ResourceInterface::TYPE_X_OBJECT);
                if ($xObjects === false) {
                    return;
                }

                $xObject = $xObjects->getValue($arguments[0]->getValue());
                $xObject = XObject::get($xObject);

                if ($xObject instanceof Form) {
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