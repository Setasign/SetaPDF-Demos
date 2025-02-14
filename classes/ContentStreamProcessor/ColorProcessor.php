<?php

namespace com\setasign\SetaPDF\Demos\ContentStreamProcessor;

use com\setasign\SetaPDF\Demos\Inspector\ColorInspector;
use setasign\SetaPDF2\Core\Canvas;
use setasign\SetaPDF2\Core\ColorSpace;
use setasign\SetaPDF2\Core\ColorSpace\DeviceN;
use setasign\SetaPDF2\Core\ColorSpace\IccBased;
use setasign\SetaPDF2\Core\ColorSpace\Indexed;
use setasign\SetaPDF2\Core\ColorSpace\Separation;
use setasign\SetaPDF2\Core\DataStructure\Color;
use setasign\SetaPDF2\Core\DataStructure\Color\Cmyk;
use setasign\SetaPDF2\Core\DataStructure\Color\Rgb;
use setasign\SetaPDF2\Core\Filter\Exception as FilterException;
use setasign\SetaPDF2\Core\Parser\Content;
use setasign\SetaPDF2\Core\Resource;
use setasign\SetaPDF2\Core\TransparencyGroup;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfIndirectReference;
use setasign\SetaPDF2\Core\Type\PdfStream;
use setasign\SetaPDF2\Core\XObject;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\Core\XObject\Image;
use setasign\SetaPDF2\Exception;
use setasign\SetaPDF2\Exception\NotImplemented;

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
     * @var Canvas
     */
    protected $_canvas;

    /**
     * @var Content
     */
    protected $_parser;

    /**
     * The constructor
     *
     * @param Canvas $canvas
     * @param ColorInspector $colorInspector
     */
    public function __construct(Canvas $canvas, ColorInspector $colorInspector)
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
    public function _color(array $args, string $operator)
    {
        $color = Color::createByComponents($args);

        $info = 'Standard color operator (' . $operator . ') in content stream.';
        if ($color instanceof Rgb) {
            $colorSpace = 'DeviceRGB';
        } elseif ($color instanceof Cmyk) {
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
     * @throws Exception
     */
    public function _colorSpace(array $args, $operator)
    {
        $colorSpace = $args[0];
        $colorSpaces = $this->_canvas->getResources(true, false, Resource::TYPE_COLOR_SPACE);
        if ($colorSpaces && $colorSpaces->offsetExists($colorSpace->getValue())) {
            $colorSpace = $colorSpaces->getValue($colorSpace->getValue());
        }

        $colorSpace = ColorSpace::createByDefinition($colorSpace);

        $info = 'Color space operator (' . $operator . ') in content stream.';
        $this->_resolveColorSpace($colorSpace, $info);
    }

    /**
     * Helper method to recursively resolve color space and their alternate color spaces
     *
     * @param ColorSpace $colorSpace
     * @param string $info
     * @throws Exception
     */
    protected function _resolveColorSpace(ColorSpace $colorSpace, string $info)
    {
        $this->_colorInspector->addFoundColor($colorSpace->getFamily(), $colorSpace, $info);

        if ($colorSpace instanceof Separation) {
            $alternate = $colorSpace->getAlternateColorSpace();
            $info = 'Alternate color space for Separation color space.';
            $this->_resolveColorSpace($alternate, $info);

        } elseif ($colorSpace instanceof DeviceN) {
            $alternate = $colorSpace->getAlternateColorSpace();
            $info = 'Alternate color space for DeviceN color space.';
            $this->_resolveColorSpace($alternate, $info);

        } elseif ($colorSpace instanceof Indexed) {
            $base = $colorSpace->getBase();
            $info = 'Base color space for Indexed color space.';
            $this->_resolveColorSpace($base, $info);

        } elseif ($colorSpace instanceof IccBased) {
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
     * @throws Exception
     * @throws NotImplemented
     */
    public function _paintXObject(array $args)
    {
        $name = $args[0]->getValue();
        $xObjects = $this->_canvas->getResources(true, false, Resource::TYPE_X_OBJECT);

        if ($xObjects === false) {
            return;
        }

        $xObjectIndirectObject = $xObjects->getValue($name);
        if (!($xObjectIndirectObject instanceof PdfIndirectReference)) {
            return;
        }

        $xObject = XObject::get($xObjectIndirectObject);
        if ($xObject instanceof Image) {
            $dict = $xObject->getIndirectObject()->ensure()->getValue();
            if ($dict->offsetExists('ImageMask') && $dict->getValue('ImageMask')->ensure()->getValue() === true) {
                return;
            }

            $colorSpace = $xObject->getColorSpace();
            $info = 'Color space of an image used in a content stream.';
            $this->_resolveColorSpace($colorSpace, $info);

        } elseif ($xObject instanceof Form) {

            /* Get the colorspace from the transparency group */
            $group = $xObject->getGroup();
            if ($group instanceof TransparencyGroup) {
                $colorSpace = $group->getColorSpace();
                if ($colorSpace !== null) {
                    $info = 'Color space from Transparency Group of XObject.';
                    $this->_resolveColorSpace($colorSpace, $info);
                }
            }

            /* We got a Form XObject - start recursive processing
             */
            $streamProcessor = new self($xObject->getCanvas(), $this->_colorInspector);
            $streamProcessor->process();
        }
    }

    /**
     * Callback for inline image operator
     *
     * @param array $args
     */
    public function _startInlineImageData(array $args)
    {
        $dict = new PdfDictionary();

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
            $colorSpace, ColorSpace::createByDefinition($colorSpace), $info
        );
    }

    /**
     * Callback for shading operator
     *
     * @param array $args
     * @throws Exception
     */
    public function _paintShapeAndColourShading($args)
    {
        $name = $args[0]->getValue();
        $shadings = $this->_canvas->getResources(true, false, Resource::TYPE_SHADING);

        if ($shadings === false) {
            return;
        }

        $shadingIndirectObject = $shadings->getValue($name);
        if (!($shadingIndirectObject instanceof PdfIndirectReference)) {
            return;
        }

        try {
            /** @var PdfDictionary $shading */
            $shading = $shadingIndirectObject->ensure();
        } catch (Exception $e) {
            return;
        }

        if ($shading instanceof PdfStream) {
            $shading = $shading->getValue();
        }

        $colorSpaceValue = $shading->getValue('ColorSpace');
        if ($colorSpaceValue === null) {
            return;
        }

        $colorSpace = ColorSpace::createByDefinition($colorSpaceValue);
        $info = 'Paint shading operator in content stream.';
        $this->_resolveColorSpace($colorSpace, $info);
    }

    /**
     * Process the content stream
     */
    public function process()
    {
        try {
            $stream = $this->_canvas->getStream();
        } catch (FilterException $e) {
            // if a stream cannot be unfiltered, we ignore it
            return;
        }

        $this->_parser = new Content($stream);

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
