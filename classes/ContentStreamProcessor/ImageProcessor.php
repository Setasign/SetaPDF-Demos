<?php

namespace setasign\SetaPDF2\Demos\ContentStreamProcessor;

use setasign\SetaPDF2\Core\Canvas\Canvas;
use setasign\SetaPDF2\Core\Canvas\GraphicState;
use setasign\SetaPDF2\Core\Filter\Exception as FilterException;
use setasign\SetaPDF2\Core\Geometry\Vector;
use setasign\SetaPDF2\Core\Parser\Content;
use setasign\SetaPDF2\Core\Resource\ResourceInterface;
use setasign\SetaPDF2\Core\Type\PdfIndirectReference;
use setasign\SetaPDF2\Core\XObject\XObject;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\NotImplementedException;

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
     * @var GraphicState
     */
    protected $_graphicState;

    /**
     * The content parser instance.
     *
     * @var Content
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
     * The parameters are the content stream and its resource dictionary.
     *
     * @param Canvas $canvas
     * @param bool $switchWidthAndHeight
     * @param GraphicState|null $graphicState
     */
    public function __construct(
        Canvas $canvas,
        bool $switchWidthAndHeight,
        ?GraphicState $graphicState = null
    ) {
        $this->_canvas = $canvas;
        $this->_switchWidthAndHeight = $switchWidthAndHeight;
        $this->_graphicState = $graphicState === null ? new GraphicState() : $graphicState;
    }

    /**
     * Get the graphic state.
     *
     * @return GraphicState
     */
    public function getGraphicState(): GraphicState
    {
        return $this->_graphicState;
    }

    /**
     * Process the content stream and return the resolved data.
     *
     * @return array
     */
    public function process(): array
    {
        $parser = $this->_getContentParser();
        $parser->process();

        return $this->_result;
    }

    /**
     * A method to receive the content parser instance.
     *
     * @return Content
     */
    protected function _getContentParser()
    {
        if ($this->_contentParser === null) {
            try {
                $stream = $this->_canvas->getStream();
            } catch (FilterException $e) {
                // if a stream cannot be unfiltered, we ignore it
                $stream = '';
            }

            $this->_contentParser = new Content($stream);
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
     * @return bool
     */
    public function _onInlineImageData(array $arguments): bool
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

        $parser->reset($pos + $offset + ((int) $m[0][1]) + strlen($m[0][0]));
        return true;
    }

    /**
     * Callback for the content parser which is called if a graphic state token (q/Q) is found.
     *
     * @param array $arguments
     * @param string $operator
     */
    public function _onGraphicStateChange(array $arguments, string $operator)
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
     */
    public function _onCurrentTransformationMatrix(array $arguments)
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
     * @throws NotImplementedException
     */
    public function _onFormXObject(array $arguments)
    {
        $xObjects = $this->_canvas->getResources(true, false, ResourceInterface::TYPE_X_OBJECT);
        if ($xObjects === null) {
            return;
        }

        $xObjects = $xObjects->ensure();
        $xObject = $xObjects->getValue($arguments[0]->getValue());

        if (!($xObject instanceof PdfIndirectReference)) {
            return;
        }

        $xObjectReference = $xObject;
        $xObject = XObject::get($xObject);

        if ($xObject instanceof Form) {
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
        $ll = $gs->toUserSpace(new Vector(0, 0, 1));
        $ul = $gs->toUserSpace(new Vector(0, 1, 1));
        $ur = $gs->toUserSpace(new Vector(1, 1, 1));
        $lr = $gs->toUserSpace(new Vector(1, 0, 1));

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
