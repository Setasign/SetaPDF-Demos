<?php

namespace com\setasign\SetaPDF\Demos\Annotation\Widget;

/**
 * Example class representing a push-button.
 */
class Pushbutton extends \SetaPDF_Core_Document_Page_Annotation_Widget
{
    /**
     * @var \SetaPDF_Core_Document
     */
    protected $_document;

    /**
     * Creates a new button field in a specific document
     *
     * @param array|\SetaPDF_Core_Type_AbstractType|\SetaPDF_Core_Type_Dictionary|\SetaPDF_Core_Type_IndirectObjectInterface $objectOrDictionary
     * @param $fieldName
     * @param \SetaPDF_Core_Document $document
     */
    public function __construct($objectOrDictionary, $fieldName, \SetaPDF_Core_Document $document)
    {
        $this->_document = $document;

        parent::__construct($objectOrDictionary);
        $dict = $this->getDictionary();
        $dict['FT'] = new \SetaPDF_Core_Type_Name('Btn');
        $this->setFieldFlags(0x010000); // pushbutton -> 17

        $acroForm = $document->getCatalog()->getAcroForm();
        $acroForm->addDefaultEntriesAndValues();

        // Ensure unique field name
        $fieldNames = [];
        foreach ($acroForm->getTerminalFieldsObjects() as $terminalObject) {
            /** @var string $name */
            $name = \SetaPDF_Core_Document_Catalog_AcroForm::resolveFieldName($terminalObject->ensure());
            $fieldNames[$name] = $name;
        }

        $i = 1;
        $oFieldName = $fieldName;
        /** @var string $fieldName */
        $fieldName = str_replace('.', '_', $fieldName);
        while (isset($fieldNames[$fieldName])) {
            $fieldName = $oFieldName . '_' . ($i++);
        }

        $dict['T'] = new \SetaPDF_Core_Type_String(\SetaPDF_Core_Encoding::toPdfString($fieldName));
    }

    /**
     * Set the button caption
     *
     * @param $caption
     * @param string $encoding
     */
    public function setCaption($caption, $encoding = 'UTF-8')
    {
        /** @var \SetaPDF_Core_Document_Page_Annotation_AppearanceCharacteristics $appCharacteristics */
        $appCharacteristics = $this->getAppearanceCharacteristics(true);
        $dict = $appCharacteristics->getDictionary();
        $dict['CA'] = new \SetaPDF_Core_Type_String(\SetaPDF_Core_Encoding::toPdfString($caption, $encoding));
    }

    /**
     * Set the font
     *
     * @param \SetaPDF_Core_Font_FontInterface $font
     * @throws \SetaPDF_FormFiller_Field_Exception
     */
    public function setFont(\SetaPDF_Core_Font_FontInterface $font)
    {
        $daValues = $this->_getDaValues();

        $writer = new \SetaPDF_Core_Writer();
        \SetaPDF_Core_Type_Name::writePdfString($writer, $this->_document->getCatalog()->getAcroForm()->addResource($font));
        $daValues['fontSize']->writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $daValues['color']->draw($writer, false);

        $this->_annotationDictionary['DA'] = new \SetaPDF_Core_Type_String($writer);
    }

    /**
     * Get the font
     *
     * @return \SetaPDF_Core_Font
     * @throws \SetaPDF_FormFiller_Field_Exception
     */
    public function getFont()
    {
        $daValues = $this->_getDaValues();
        $fonts = $this->_document->getCatalog()->getAcroForm()->getDefaultResources(true, \SetaPDF_Core_Resource::TYPE_FONT);

        return \SetaPDF_Core_Font::get($fonts->getValue($daValues['fontName']->getValue()));
    }

    /**
     * Set the font size
     *
     * @param int|float $fontSize
     * @throws \SetaPDF_FormFiller_Field_Exception
     */
    public function setFontSize($fontSize)
    {
        $daValues = $this->_getDaValues();

        $writer = new \SetaPDF_Core_Writer();
        $daValues['fontSize'] = new \SetaPDF_Core_Type_Numeric($fontSize);
        \SetaPDF_Core_Type_Name::writePdfString($writer, $daValues['fontName']->getValue());
        \SetaPDF_Core_Type_Numeric::writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $daValues['color']->draw($writer, false);

        $this->_annotationDictionary['DA'] = new \SetaPDF_Core_Type_String($writer);
    }

    /**
     * Set the text color
     *
     * @param int|float|string|array|\SetaPDF_Core_Type_Array|\SetaPDF_Core_DataStructure_Color $color
     * @throws \SetaPDF_FormFiller_Field_Exception
     */
    public function setTextColor($color)
    {
        if (!$color instanceof \SetaPDF_Core_DataStructure_Color) {
            $color = \SetaPDF_Core_DataStructure_Color::createByComponents($color);
        }

        $daValues = $this->_getDaValues();

        $writer = new \SetaPDF_Core_Writer();
        \SetaPDF_Core_Type_Name::writePdfString($writer, $daValues['fontName']->getValue());
        \SetaPDF_Core_Type_Numeric::writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $color->draw($writer, false);

        $this->_annotationDictionary['DA'] = new \SetaPDF_Core_Type_String($writer);
    }

    /**
     * Get default appearance values
     *
     * @return array
     * @throws \SetaPDF_FormFiller_Field_Exception
     */
    protected function _getDaValues()
    {
        $da = \SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute($this->_annotationDictionary, 'DA');
        $da = $da ? $da : \SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute(
            $this->_document->getCatalog()->getAcroForm()->getDictionary(),
            'DA'
        );

        if (!$da) {
            throw new \SetaPDF_FormFiller_Field_Exception('No DA key found.');
        }

        $fontName = $fontSize = $color = null;
        $parser = new \SetaPDF_Core_Parser_Content($da->getValue());
        $parser->registerOperator('Tf', static function($params) use (&$fontName, &$fontSize) {
            $fontName = $params[0];
            $fontSize = $params[1];
        });
        $parser->registerOperator(['g', 'rg', 'k'], static function($params) use (&$color) {
            $color = \SetaPDF_Core_DataStructure_Color::createByComponents($params);
        });

        $parser->process();

        return [
            'fontName' => $fontName,
            'fontSize' => $fontSize,
            'color' => $color
        ];
    }

    /**
     * Creates the appearance of the button
     */
    protected function _createAppearance()
    {
        $document = $this->_document;

        $width = $this->getWidth();
        $height = $this->getHeight();

        $n = $this->getAppearance('N');
        if (!$n) {
            $n = \SetaPDF_Core_XObject_Form::create($document, [0, 0, $width, $height]);
        }
        $this->setAppearance($n);

        $canvas = $n->getCanvas();

        $appearanceCharacteristics = $this->getAppearanceCharacteristics();
        $borderStyle = $this->getBorderStyle();
        $borderWidth = 0;
        $_borderStyle = \SetaPDF_Core_Document_Page_Annotation_BorderStyle::SOLID;

        if ($borderStyle) {
            $_borderStyle = $borderStyle->getStyle();
            $borderWidth = $borderStyle->getWidth();
        }

        if ($borderWidth == 0 && $appearanceCharacteristics && $appearanceCharacteristics->getBorderColor() !== null) {
            $borderWidth = 1;
        }

        // Handle Rotation
        $rotation = $appearanceCharacteristics
            ? $appearanceCharacteristics->getRotation()
            : 0;
        if ($rotation != 0) {
            $rotation %= 360;
            if ($rotation < 0) {
                $rotation += 360;
            }

            $r = \deg2rad($rotation);
            $a = $d = \cos($r);
            $b = \sin($r);
            $c = -$b;
            $e = 0;
            $f = 0;

            if ($a == -1) {
                $e = $width;
                $f = $height;
            }

            if ($b == 1) {
                $e = $height;
            }

            if ($c == 1) {
                $f = $width;
            }

            $n->getObject()->ensure()->getValue()->offsetSet('Matrix', new \SetaPDF_Core_Type_Array([
                new \SetaPDF_Core_Type_Numeric($a),
                new \SetaPDF_Core_Type_Numeric($b),
                new \SetaPDF_Core_Type_Numeric($c),
                new \SetaPDF_Core_Type_Numeric($d),
                new \SetaPDF_Core_Type_Numeric($e),
                new \SetaPDF_Core_Type_Numeric($f)
            ]));
        }

        // Draw Background
        $backgroundColor = $appearanceCharacteristics
            ? $appearanceCharacteristics->getBackgroundColor()
            : null;
        if ($backgroundColor) {
            $backgroundColor->draw($canvas, false);
            $canvas->draw()->rect(0, 0, $width, $height, \SetaPDF_Core_Canvas_Draw::STYLE_FILL);
        }

        // Draw Border:
        $borderColor = $appearanceCharacteristics
            ? $appearanceCharacteristics->getBorderColor()
            : null;

        // It is possible to have no border but only a border style!

        // Beveld or Inset
        if ($_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::BEVELED ||
            $_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::INSET) {
            $colorLtValue = 1; //' 1 g';
            if ($_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::INSET) {
                $colorLtValue = .5; // ' 0.5 g';
            }

            /**
             * This color adjustment is not needed for list boxes.
             * The effect will only occur if the field is active
             * All other fields will use this effect.
             */
            if (
                $_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::BEVELED && $backgroundColor
            ) {
                $tmpColor = clone $backgroundColor;
                $tmpColor->adjustAllComponents(-0.250977);
                $colorRb = $tmpColor;
            } else {
                $colorRb = new \SetaPDF_Core_DataStructure_Color_Gray(.75);
            }

            // Draw the inner border
            $canvas->saveGraphicState();  // q
            \SetaPDF_Core_DataStructure_Color_Gray::writePdfString($canvas, $colorLtValue, false);

            $_borderWidth = $borderWidth * 2;

            $canvas->path()
                ->moveTo($x = $_borderWidth / 2, $y = $height-$_borderWidth / 2)
                ->lineTo($x = $width - $x, $y)
                ->lineTo($x -= $_borderWidth / 2, $y -= $_borderWidth / 2)
                ->lineTo($x = $_borderWidth, $y)
                ->lineTo($x, $y = $_borderWidth)
                ->lineTo($x /= 2, $y /= 2)
                ->close()
                ->fill();

            $colorRb->draw($canvas, false);

            $canvas->path()
                ->moveTo($x, $y)
                ->lineTo($x *= 2, $y *= 2)
                ->lineTo($x = $width - $x, $y)
                ->lineTo($x, $y += $height - $_borderWidth * 2)
                ->lineTo($x += $_borderWidth / 2, $y += $_borderWidth / 2)
                ->lineTo($x, $_borderWidth / 2)
                ->close()
                ->fill();

            $canvas->restoreGraphicState(); // Q
        }

        if ($borderColor) {
            $canvas->path()->setLineWidth($borderWidth);
            $borderColor->draw($canvas, true);

            // Dashed
            if ($_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::DASHED) {
                $canvas->path()->setDashPattern($borderStyle->getDashPattern());
            }

            // Draw border
            // NOT underline
            if ($_borderStyle !== \SetaPDF_Core_Document_Page_Annotation_BorderStyle::UNDERLINE) {
                $canvas->draw()->rect(
                    $borderWidth * .5,
                    $borderWidth * .5,
                    $width - $borderWidth,
                    $height - $borderWidth
                );

                // underline
            } else {
                $y = $borderWidth / 2;
                $canvas->draw()->line(0, $y, $width, $y);
            }
        }

        $daValues = $this->_getDaValues();

        $font = $this->getFont();
        $textBlock = new \SetaPDF_Core_Text_Block($font, null);

        $borderDoubled = (
            $_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::BEVELED ||
            $_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::INSET
        );

        $offset = max(1, $borderWidth * ($borderDoubled ? 2 : 1)) * 2;

        /** @var \SetaPDF_Core_Document_Page_Annotation_AppearanceCharacteristics $appCharacteristics */
        $appCharacteristics = $this->getAppearanceCharacteristics(true);
        $dict = $appCharacteristics->getDictionary();
        $textBlock->setText(\SetaPDF_Core_Encoding::convertPdfString(
            $dict->getValue('CA')->getValue(), 'UTF-16BE'
        ), 'UTF-16BE');
        $textBlock->setPadding($offset);
        $fontSize = $daValues['fontSize']->getValue();
        if ($fontSize == 0) {
            $textBlock->setFontSize(12);
            $textWidthAt12Points = $textBlock->getTextWidth();

            $fontSize = ($width - $offset * 2) / $textWidthAt12Points * 12;
            $textBlock->setFontSize($fontSize);

            $textHeight = $textBlock->getTextHeight();
            if ($textHeight > ($height - $offset * 2)) {
                // A near value...
                $fontSize = ($height - $offset * 2) * $fontSize / $textHeight;
            }
        }
        $textBlock->setWidth($width - $offset * 2);
        $textBlock->setFontSize($fontSize);
        $textBlock->setTextColor($daValues['color']);

        $textBlock->setAlign(\SetaPDF_Core_Text::ALIGN_CENTER);
        $textBlock->draw($canvas, 0, $height / 2 - $textBlock->getHeight() / 2);
    }

    /**
     * @return \SetaPDF_Core_Type_IndirectObjectInterface
     */
    public function getIndirectObject(\SetaPDF_Core_Document $document = null)
    {
        $this->_createAppearance();
        return parent::getIndirectObject($document);
    }

    /**
     * Sets a field flag
     *
     * @param integer $flags
     * @param boolean|null $add Add = true, remove = false, set = null
     */
    public function setFieldFlags($flags, $add = true)
    {
        if ($add === false) {
            $this->unsetFieldFlags($flags);
            return;
        }

        $dict = \SetaPDF_Core_Type_Dictionary_Helper::resolveDictionaryByAttribute($this->_annotationDictionary, 'Ff');

        if ($dict instanceof \SetaPDF_Core_Type_AbstractType) {
            $value = $dict->ensure()->getValue('Ff');
            if ($add === true) {
                $value->setValue($value->getValue() | $flags);
            } else {
                $value->setValue($flags);
            }

        } else {
            $this->_annotationDictionary->offsetSet('Ff', new \SetaPDF_Core_Type_Numeric($flags));
        }
    }

    /**
     * Removes a field flag
     *
     * @param integer $flags
     */
    public function unsetFieldFlags($flags)
    {
        $dict = \SetaPDF_Core_Type_Dictionary_Helper::resolveDictionaryByAttribute($this->_annotationDictionary, 'Ff');

        if ($dict instanceof \SetaPDF_Core_Type_AbstractType) {
            $value = $dict->ensure()->getValue('Ff');
            $value->setValue($value->getValue() & ~$flags);
        }
    }

    /**
     * Returns the current field flags
     *
     * @return integer
     */
    public function getFieldFlags()
    {
        $fieldFlags = \SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute($this->_annotationDictionary, 'Ff');
        if ($fieldFlags) {
            return $fieldFlags->getValue();
        }

        return 0;
    }

    /**
     * Checks if a specific field flag is set
     *
     * @param integer $flag
     * @return boolean
     */
    public function isFieldFlagSet($flag)
    {
        return ($this->getFieldFlags() & $flag) !== 0;
    }
}
