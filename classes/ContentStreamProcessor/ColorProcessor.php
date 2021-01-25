<?php

namespace com\setasign\SetaPDF\Demos\ContentStreamProcessor;

use com\setasign\SetaPDF\Demos\Inspector\ColorInspector;

/**
 * Class ColorsProcessor
 *
 * This class offer the desired callback methods for the content stream parser
 */
class ColorProcessor
{
    /**
     * @var ColorInspector
     */
    protected $_colorInspector;

    /**
     * @var \SetaPDF_Core_Canvas
     */
    protected $_canvas;

    /**
     * @var \SetaPDF_Core_Parser_Content
     */
    protected $_parser;

    /**
     * The constructor
     *
     * @param \SetaPDF_Core_Canvas $canvas
     * @param ColorInspector $colorInspector
     */
    public function __construct(\SetaPDF_Core_Canvas $canvas, ColorInspector $colorInspector)
    {
        $this->_canvas = $canvas;
        $this->_colorInspector = $colorInspector;
    }

    /**
     * Callback for standard color operators
     *
     * @param array $args
     * @param string $operator
     */
    public function _color(array $args, $operator)
    {
        $color = \SetaPDF_Core_DataStructure_Color::createByComponents($args);

        $info = 'Standard color operator (' . $operator . ') in content stream.';
        if ($color instanceof \SetaPDF_Core_DataStructure_Color_Rgb) {
            $colorSpace = 'DeviceRGB';
        } elseif ($color instanceof \SetaPDF_Core_DataStructure_Color_Cmyk) {
            $colorSpace = 'DeviceCMYK';
        } else {
            $colorSpace = 'DeviceGray';
        }

        $this->_colorInspector->addFoundColor($colorSpace, $color, $info);
    }

    /**
     * Callback for color space operators
     *
     * @param array $args
     * @param string $operator
     * @throws \SetaPDF_Core_Exception
     */
    public function _colorSpace(array $args, $operator)
    {
        $colorSpace = $args[0];
        $colorSpaces = $this->_canvas->getResources(true, false, \SetaPDF_Core_Resource::TYPE_COLOR_SPACE);
        if ($colorSpaces && $colorSpaces->offsetExists($colorSpace->getValue())) {
            $colorSpace = $colorSpaces->getValue($colorSpace->getValue());
        }

        $colorSpace = \SetaPDF_Core_ColorSpace::createByDefinition($colorSpace);

        $info = 'Color space operator (' . $operator . ') in content stream.';
        $this->_resolveColorSpace($colorSpace, $info);
    }

    /**
     * Helper method to recursily resolve color space and their alternate color spaces
     *
     * @param \SetaPDF_Core_ColorSpace $colorSpace
     * @param string $info
     * @throws \SetaPDF_Core_Exception
     */
    protected function _resolveColorSpace(\SetaPDF_Core_ColorSpace $colorSpace, $info)
    {
        $this->_colorInspector->addFoundColor($colorSpace->getFamily(), $colorSpace, $info);

        if ($colorSpace instanceof \SetaPDF_Core_ColorSpace_Separation) {
            $alternate = $colorSpace->getAlternateColorSpace();
            $info = 'Alternate color space for Separation color space.';
            $this->_resolveColorSpace($alternate, $info);

        } elseif ($colorSpace instanceof \SetaPDF_Core_ColorSpace_DeviceN) {
            $alternate = $colorSpace->getAlternateColorSpace();
            $info = 'Alternate color space for DeviceN color space.';
            $this->_resolveColorSpace($alternate, $info);

        } elseif ($colorSpace instanceof \SetaPDF_Core_ColorSpace_Indexed) {
            $base = $colorSpace->getBase();
            $info = 'Base color space for Indexed color space.';
            $this->_resolveColorSpace($base, $info);

        } elseif ($colorSpace instanceof \SetaPDF_Core_ColorSpace_IccBased) {
            $stream = $colorSpace->getIccProfileStream();
            $alternate = $stream->getAlternate();
            if ($alternate) {
                $info = 'Alternate color space for ICC profile color space.';
                $this->_resolveColorSpace($alternate, $info);
            }

            /* See ICC.1:2010 - Table 19 (ICC1v43_2010-12.pdf)
             */
            $info = 'Color space signature extracted from ICC profile.';
            $colorSpace = $stream->getParser()->getColorSpace();
            $this->_colorInspector->addFoundColor(trim($colorSpace), $stream, $info);
        }
    }

    /**
     * Callback for painting a XObject
     *
     * @param array $args
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Exception_NotImplemented
     */
    public function _paintXObject(array $args)
    {
        $name = $args[0]->getValue();
        $xObjects = $this->_canvas->getResources(true, false, \SetaPDF_Core_Resource::TYPE_X_OBJECT);

        if ($xObjects === false) {
            return;
        }

        $xObjectIndirectObject = $xObjects->getValue($name);
        if (!($xObjectIndirectObject instanceof \SetaPDF_Core_Type_IndirectReference)) {
            return;
        }

        $xObject = \SetaPDF_Core_XObject::get($xObjectIndirectObject);
        if ($xObject instanceof \SetaPDF_Core_XObject_Image) {
            $dict = $xObject->getIndirectObject()->ensure()->getValue();
            if ($dict->offsetExists('ImageMask') && $dict->getValue('ImageMask')->ensure()->getValue() === true) {
                return;
            }

            $colorSpace = $xObject->getColorSpace();
            $info = 'Color space of an image used in a content stream.';
            $this->_resolveColorSpace($colorSpace, $info);

        } elseif ($xObject instanceof \SetaPDF_Core_XObject_Form) {

            /* Get the colorspace from the transparency group */
            $group = $xObject->getGroup();
            if ($group instanceof \SetaPDF_Core_TransparencyGroup) {
                $colorSpace = $group->getColorSpace();
                if ($colorSpace !== null) {
                    $info = 'Color space from Transparency Group of XObject.';
                    $this->_resolveColorSpace(\SetaPDF_Core_ColorSpace::createByDefinition($colorSpace), $info);
                }
            }

            /* We got a Form XObject - start recusrive processing
             */
            $streamProcessor = new self($xObject->getCanvas(), $this->_colorInspector);
            $streamProcessor->process();
        }
    }

    /**
     * Callback for inline image operator
     *
     * @param $args
     */
    public function _startInlineImageData($args)
    {
        $dict = new \SetaPDF_Core_Type_Dictionary();

        for ($i = 0, $c = count($args); $i < $c; $i += 2) {
            $dict[$args[$i]] = $args[$i + 1];
        }

        $colorSpace = $dict->offsetExists('CS') ? $dict->getValue('CS') : $dict->getValue('ColorSpace');
        if ($colorSpace === null) {
            return;
        }

        $colorSpace = $colorSpace->getValue();

        switch ($colorSpace) {
            case 'G':
                $colorSpace = 'DeviceGray';
                break;
            case 'RGB':
                $colorSpace = 'DeviceRGB';
                break;
            case 'CMYK':
                $colorSpace = 'DeviceCMYK';
                break;
            case 'I':
                $colorSpace = 'Indexed';
                break;
        }

        $info = 'Color space of an inline image in content stream.';
        $this->_colorInspector->addFoundColor(
            $colorSpace, \SetaPDF_Core_ColorSpace::createByDefinition($colorSpace), $info
        );
    }

    /**
     * Callback for shading operator
     *
     * @param array $args
     * @throws \SetaPDF_Core_Exception
     */
    public function _paintShapeAndColourShading($args)
    {
        $name = $args[0]->getValue();
        $shadings = $this->_canvas->getResources(true, false, \SetaPDF_Core_Resource::TYPE_SHADING);

        if ($shadings === false) {
            return;
        }

        $shadingIndirectObject = $shadings->getValue($name);
        if (!($shadingIndirectObject instanceof \SetaPDF_Core_Type_IndirectReference)) {
            return;
        }

        try {
            /** @var \SetaPDF_Core_Type_Dictionary $shading */
            $shading = $shadingIndirectObject->ensure();
        } catch (\SetaPDF_Core_Type_IndirectReference_Exception $e) {
            return;
        }

        if ($shading instanceof \SetaPDF_Core_Type_Stream) {
            $shading = $shading->getValue();
        }

        $colorSpaceValue = $shading->getValue('ColorSpace');
        if ($colorSpaceValue === null) {
            return;
        }

        $colorSpace = \SetaPDF_Core_ColorSpace::createByDefinition($colorSpaceValue);
        $info = 'Paint shading operator in content stream.';
        $this->_resolveColorSpace($colorSpace, $info);
    }

    /**
     * Process the content stream
     */
    public function process()
    {
        $this->_parser = new \SetaPDF_Core_Parser_Content($this->_canvas->getStream());

        /* Register colorspace operators
         * f.g. -> /DeviceRGB CS   % Set DeviceRGB colour space
         */
        $this->_parser->registerOperator(
            ['CS', 'cs'],
            [$this, '_colorSpace']
        );

        /* Register default color space operators */
        $this->_parser->registerOperator(
            ['G', 'g', 'RG', 'rg', 'K', 'k'],
            [$this, '_color']
        );

        /* Register draw operator for XObjects */
        $this->_parser->registerOperator('Do', [$this, '_paintXObject']);

        /* Inline image */
        $this->_parser->registerOperator('ID', [$this, '_startInlineImageData']);

        /* Shading Operator */
        $this->_parser->registerOperator('sh', [$this, '_paintShapeAndColourShading']);

        $this->_parser->process();
    }
}
