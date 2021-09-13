<?php

namespace com\setasign\SetaPDF\Demos\ContentStreamProcessor;

/**
 * Class ImageProcessor
 */
class ImageProcessor
{
    /**
     * The content stream.
     *
     * @var string
     */
    protected $_canvas;

    /**
     * The graphic state.
     *
     * @var \SetaPDF_Core_Canvas_GraphicState
     */
    protected $_graphicState;

    /**
     * The content parser instance.
     *
     * @var \SetaPDF_Core_Parser_Content
     */
    protected $_contentParser;

    /**
     * The result data.
     *
     * @var array
     */
    protected $_result = [];

    /**
     * Switch the width and height values.
     *
     * @var bool
     */
    protected $_switchWidthAndHeight = false;

    /**
     * The constructor.
     *
     * The parameter are the content stream and its resources dictionary.
     *
     * @param \SetaPDF_Core_Canvas $canvas
     * @param boolean $switchWidthAndHeight
     * @param \SetaPDF_Core_Canvas_GraphicState|null $graphicState
     */
    public function __construct(
        \SetaPDF_Core_Canvas $canvas,
        $switchWidthAndHeight,
        \SetaPDF_Core_Canvas_GraphicState $graphicState = null
    )
    {
        $this->_canvas = $canvas;
        $this->_switchWidthAndHeight = $switchWidthAndHeight;
        $this->_graphicState = $graphicState === null ? new \SetaPDF_Core_Canvas_GraphicState() : $graphicState;
    }

    /**
     * Get the graphic state.
     *
     * @return \SetaPDF_Core_Canvas_GraphicState
     */
    public function getGraphicState()
    {
        return $this->_graphicState;
    }

    /**
     * Process the content stream and return the resolved data.
     *
     * @return array
     */
    public function process()
    {
        $parser = $this->_getContentParser();
        $parser->process();

        return $this->_result;
    }

    /**
     * A method to receive the content parser instance.
     *
     * @return \SetaPDF_Core_Parser_Content
     */
    protected function _getContentParser()
    {
        if ($this->_contentParser === null) {
            $this->_contentParser = new \SetaPDF_Core_Parser_Content($this->_canvas->getStream());
            $this->_contentParser->registerOperator(['q', 'Q'], [$this, '_onGraphicStateChange']);
            $this->_contentParser->registerOperator('cm', [$this, '_onCurrentTransformationMatrix']);
            $this->_contentParser->registerOperator('Do', [$this, '_onFormXObject']);
            $this->_contentParser->registerOperator('ID', [$this, '_onInlineImageData']);
        }

        return $this->_contentParser;
    }

    /**
     * Callback for inline image data operator
     *
     * @param array $arguments
     * @param string $operator
     */
    public function _onInlineImageData($arguments, $operator)
    {
        $data = [];
        for ($i = 0, $c = count($arguments); $i < $c; $i += 2) {
            $data[$arguments[$i]->getValue()] = $arguments[$i + 1];
        }

        if (!(isset($data['W']) || isset($data['Width'])) || !(isset($data['H']) || isset($data['Height']))) {
            return true;
        }

        $pixelWidth = isset($data['W']) ? $data['W']->getValue() : $data['Width']->getValue();
        $pixelHeight = isset($data['H']) ? $data['H']->getValue() : $data['Height']->getValue();

        $this->_result[] = $this->_getNewResult($pixelWidth, $pixelHeight);

        $parser = $this->_contentParser->getParser();
        $reader = $parser->getReader();

        $pos = $reader->getPos();
        $offset = $reader->getOffset();

        while (
            (\preg_match(
                '/EI[\x00\x09\x0A\x0C\x0D\x20]/',
                $reader->getBuffer(),
                $m,
                PREG_OFFSET_CAPTURE
            )) === 0
        ) {
            if ($reader->increaseLength(1000) === false) {
                return false;
            }
        }

        $parser->reset($pos + $offset + $m[0][1] + strlen($m[0][0]));
    }

    /**
     * Callback for the content parser which is called if a graphic state token (q/Q) is found.
     *
     * @param array $arguments
     * @param string $operator
     */
    public function _onGraphicStateChange($arguments, $operator)
    {
        if ($operator === 'q') {
            $this->getGraphicState()->save();
        } else {
            $this->getGraphicState()->restore();
        }
    }

    /**
     * Callback for the content parser which is called if a "cm" token is found.
     *
     * @param array $arguments
     * @param string $operator
     */
    public function _onCurrentTransformationMatrix($arguments, $operator)
    {
        $this->getGraphicState()->addCurrentTransformationMatrix(
            $arguments[0]->getValue(), $arguments[1]->getValue(),
            $arguments[2]->getValue(), $arguments[3]->getValue(),
            $arguments[4]->getValue(), $arguments[5]->getValue()
        );
    }

    /**
     * Callback for the content parser which is called if a "Do" operator/token is found.
     *
     * @param array $arguments
     *
     * @throws \SetaPDF_Exception_NotImplemented
     */
    public function _onFormXObject($arguments)
    {
        $xObjects = $this->_canvas->getResources(true, false, \SetaPDF_Core_Resource::TYPE_X_OBJECT);
        if ($xObjects === null) {
            return;
        }

        $xObjects = $xObjects->ensure();
        $xObject = $xObjects->getValue($arguments[0]->getValue());

        if (!($xObject instanceof \SetaPDF_Core_Type_IndirectReference)) {
            return;
        }

        $xObjectReference = $xObject;
        $xObject = \SetaPDF_Core_XObject::get($xObject);

        if ($xObject instanceof \SetaPDF_Core_XObject_Form) {
            /* In that case we need to create a new instance of the processor and process
             * the form xobjects stream.
             */

            $gs = $this->getGraphicState();
            $gs->save();
            $dict = $xObject->getIndirectObject()->ensure()->getValue();
            $matrix = $dict->getValue('Matrix');
            if ($matrix) {
                $matrix = $matrix->ensure()->toPhp();
                $gs->addCurrentTransformationMatrix(
                    $matrix[0], $matrix[1], $matrix[2], $matrix[3], $matrix[4], $matrix[5]
                );
            }

            $processor = new self($xObject->getCanvas(), $this->_switchWidthAndHeight, $gs);

            foreach ($processor->process() AS $image) {
                $this->_result[] = $image;
            }

            $gs->restore();

        } else {
            $newResult = $this->_getNewResult($xObject->getWidth(), $xObject->getHeight());
            $newResult['objectReference'] = $xObjectReference;

            $this->_result[] = $newResult;
        }
    }

    /**
     * Helper method to create a result entry.
     *
     * @param numeric $pixelWidth
     * @param numeric $pixelHeight
     * @return array
     */
    protected function _getNewResult($pixelWidth, $pixelHeight)
    {
        // we have an image object, calculate it's outer points in user space
        $gs = $this->getGraphicState();
        $ll = $gs->toUserSpace(new \SetaPDF_Core_Geometry_Vector(0, 0, 1));
        $ul = $gs->toUserSpace(new \SetaPDF_Core_Geometry_Vector(0, 1, 1));
        $ur = $gs->toUserSpace(new \SetaPDF_Core_Geometry_Vector(1, 1, 1));
        $lr = $gs->toUserSpace(new \SetaPDF_Core_Geometry_Vector(1, 0, 1));

        // ...and match some further information
        $width  = \abs($this->_switchWidthAndHeight ? $ur->subtract($ll)->getY() : $ur->subtract($ll)->getX());
        $height = \abs($this->_switchWidthAndHeight ? $ur->subtract($ll)->getX() : $ur->subtract($ll)->getY());

       return [
            'll' => $ll->toPoint(),
            'ul' => $ul->toPoint(),
            'ur' => $ur->toPoint(),
            'lr' => $lr->toPoint(),
            'width' => $width,
            'height' => $height,
            'resolutionX' => $pixelWidth / $width * 72,
            'resolutionY' => $pixelHeight / $height * 72,
            'pixelWidth' => $pixelWidth,
            'pixelHeight' => $pixelHeight
        ];
    }
}
