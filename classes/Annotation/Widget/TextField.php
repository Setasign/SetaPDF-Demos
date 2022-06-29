<?php

namespace com\setasign\SetaPDF\Demos\Annotation\Widget;

/**
 * Example class representing a text field.
 */
class TextField extends \SetaPDF_Core_Document_Page_Annotation_Widget
{
    /**
     * @var \SetaPDF_Core_Document
     */
    protected $_document;

    /**
     * @var \SetaPDF_Core_Font
     */
    protected $_appearanceFont;

    /**
     * @var string
     */
    protected $_qualifiedName;

    /**
     * Creates a new text field in a specific document
     *
     * @param array|\SetaPDF_Core_Type_AbstractType|\SetaPDF_Core_Type_Dictionary|\SetaPDF_Core_Type_IndirectObjectInterface $objectOrDictionary
     * @param string $fieldName
     * @param \SetaPDF_Core_Document $document
     * @throws \SetaPDF_Core_SecHandler_Exception
     * @throws \SetaPDF_Core_Type_Exception
     * @throws \SetaPDF_Core_Type_IndirectReference_Exception
     */
    public function __construct($objectOrDictionary, $fieldName, \SetaPDF_Core_Document $document)
    {
        $this->_document = $document;

        parent::__construct($objectOrDictionary);
        $dict = $this->getDictionary();
        $dict->offsetSet('FT', new \SetaPDF_Core_Type_Name('Tx'));
        $this->setPrintFlag();

        $acroForm = $document->getCatalog()->getAcroForm();
        $acroForm->addDefaultEntriesAndValues();

        // Ensure unique field name
        $fieldNames = [];
        foreach ($acroForm->getTerminalFieldsObjects() as $terminalObject) {
            $name = \SetaPDF_Core_Document_Catalog_AcroForm::resolveFieldName($terminalObject->ensure());
            $fieldNames[$name] = $name;
        }

        $i = 1;
        $fieldName = \str_replace('.', '_', $fieldName);
        $oFieldName = $fieldName;
        while (isset($fieldNames[$fieldName])) {
            $fieldName = $oFieldName . '_' . ($i++);
        }

        $this->_qualifiedName = $fieldName;
        $dict->offsetSet('T', new \SetaPDF_Core_Type_String(\SetaPDF_Core_Encoding::toPdfString($fieldName)));
    }

    /**
     * Returns the qualified name.
     *
     * @return string
     */
    public function getQualifiedName()
    {
        return $this->_qualifiedName;
    }

    /**
     * Set the value
     *
     * @param string $value
     * @param string $encoding
     */
    public function setValue($value, $encoding = 'UTF-8')
    {
        $dict = $this->getDictionary();
        $dict->offsetSet('V', new \SetaPDF_Core_Type_String(\SetaPDF_Core_Encoding::toPdfString($value, $encoding)));
    }

    /**
     * @param array $daValues
     */
    protected function _setDaValues(array $daValues)
    {
        $writer = new \SetaPDF_Core_Writer();
        \SetaPDF_Core_Type_Name::writePdfString($writer, $daValues['fontName']->getValue());
        \SetaPDF_Core_Type_Numeric::writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $daValues['color']->draw($writer, false);

        $dict = $this->getDictionary();
        $dict->offsetSet('DA', new \SetaPDF_Core_Type_String($writer));
    }

    /**
     * Set the font
     *
     * @param \SetaPDF_Core_Font_Simple $font
     * @throws \SetaPDF_Core_SecHandler_Exception
     * @throws \SetaPDF_Core_Exception
     */
    public function setFont(\SetaPDF_Core_Font_Simple $font)
    {
        $daValues = $this->_getDaValues();
        $daValues['fontName'] = new \SetaPDF_Core_Type_Name(
            $this->_document->getCatalog()->getAcroForm()->addResource($font)
        );
        $this->_setDaValues($daValues);
    }

    /**
     * Set an individual which is used for rendering of the field value.
     *
     * @param \SetaPDF_Core_Font_FontInterface $appearanceFont
     * @return void
     */
    public function setAppearanceFont(\SetaPDF_Core_Font_FontInterface $appearanceFont)
    {
        $this->_appearanceFont = $appearanceFont;
    }

    /**
     * Get the font
     *
     * @return \SetaPDF_Core_Font
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Core_Font_Exception
     * @throws \SetaPDF_Core_SecHandler_Exception
     * @throws \SetaPDF_Core_Type_Exception
     * @throws \SetaPDF_Core_Type_IndirectReference_Exception
     * @throws \SetaPDF_Exception_NotImplemented
     */
    public function getFont()
    {
        $daValues = $this->_getDaValues();
        $fonts = $this->_document->getCatalog()->getAcroForm()->getDefaultResources(true, \SetaPDF_Core_Resource::TYPE_FONT);

        /** @var \SetaPDF_Core_Type_IndirectReference $font */
        $font = $fonts->getValue($daValues['fontName']->getValue());
        return \SetaPDF_Core_Font::get($font);
    }

    /**
     * Set the font size
     *
     * @param integer|float $fontSize
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Core_SecHandler_Exception
     */
    public function setFontSize($fontSize)
    {
        $daValues = $this->_getDaValues();
        $daValues['fontSize'] = new \SetaPDF_Core_Type_Numeric($fontSize);
        $this->_setDaValues($daValues);
    }

    /**
     * Set the text color
     *
     * @param \SetaPDF_Core_DataStructure_Color|int|float|string|array|\SetaPDF_Core_Type_Array $color
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Core_SecHandler_Exception
     */
    public function setTextColor($color)
    {
        if (!$color instanceof \SetaPDF_Core_DataStructure_Color) {
            $color = \SetaPDF_Core_DataStructure_Color::createByComponents($color);
        }

        $daValues = $this->_getDaValues();
        $daValues['color'] = $color;
        $this->_setDaValues($daValues);
    }

    /**
     * Set the form of quadding (justification / align) that shall be used in displaying the fields text.
     *
     * @see SetaPDF_Core_Text::ALIGN_LEFT
     * @see SetaPDF_Core_Text::ALIGN_CENTER
     * @see SetaPDF_Core_Text::ALIGN_RIGHT
     * @param $align
     */
    public function setAlign($align)
    {
        $allowed = [
            \SetaPDF_Core_Text::ALIGN_LEFT,
            \SetaPDF_Core_Text::ALIGN_CENTER,
            \SetaPDF_Core_Text::ALIGN_RIGHT
        ];

        if (!\in_array($align, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid align parameter "' . $align . '".');
        }

        $this->_annotationDictionary->offsetSet('Q', new \SetaPDF_Core_Type_Numeric(\array_search($align, $allowed, true)));
    }

    /**
     * Get the form of quadding (justification / align) that shall be used in displaying the fields text.
     *
     * @return mixed|string
     */
    public function getAlign()
    {
        $align = \SetaPDF_Core_Type_Dictionary_Helper::getValue($this->getDictionary(), 'Q');
        if (!$align instanceof \SetaPDF_Core_Type_Numeric) {
            return \SetaPDF_Core_Text::ALIGN_LEFT;
        }

        $align = (int)$align->getValue();
        $values = [
            \SetaPDF_Core_Text::ALIGN_LEFT,
            \SetaPDF_Core_Text::ALIGN_CENTER,
            \SetaPDF_Core_Text::ALIGN_RIGHT
        ];

        if (!isset($values[$align])) {
            return \SetaPDF_Core_Text::ALIGN_LEFT;
        }

        return $values[$align];
    }

    /**
     * Check if the multiline flag is set.
     *
     * @return boolean
     */
    public function isMultiline()
    {
        return $this->isFieldFlagSet(0x1000);
    }

    /**
     * Set the multiline flag.
     *
     * @param bool|true $multiline
     */
    public function setMultiline($multiline = true)
    {
        $this->setFieldFlags(0x1000, $multiline);
    }

    /**
     * Get default appearance values
     *
     * @return array
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Core_SecHandler_Exception
     */
    protected function _getDaValues()
    {
        $da = \SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute($this->_annotationDictionary, 'DA');
        $da = $da ?: \SetaPDF_Core_Type_Dictionary_Helper::resolveAttribute(
            $this->_document->getCatalog()->getAcroForm()->getDictionary(),
            'DA'
        );

        if (!$da) {
            throw new \SetaPDF_Core_Exception('No DA key found.');
        }

        $fontName = $fontSize = $color = null;
        $parser = new \SetaPDF_Core_Parser_Content($da->getValue());
        $parser->registerOperator('Tf', function($params) use (&$fontName, &$fontSize) {
            $fontName = $params[0];
            $fontSize = $params[1];
        });
        $parser->registerOperator(['g', 'rg', 'k'], function($params) use (&$color) {
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
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Core_Font_Exception
     * @throws \SetaPDF_Core_SecHandler_Exception
     * @throws \SetaPDF_Core_Type_Exception
     * @throws \SetaPDF_Core_Type_IndirectReference_Exception
     * @throws \SetaPDF_Exception_NotImplemented
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
            $rotation = $rotation % 360;
            if ($rotation < 0)
                $rotation = $rotation + 360;

            $r = deg2rad($rotation);
            $a = $d = cos($r);
            $b = sin($r);
            $c = -$b;
            $e = 0;
            $f = 0;

            if ($a == -1) {
                $e = $width;
                $f = $height;
            }

            if ($b == 1)
                $e = $height;

            if ($c == 1)
                $f = $width;

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
            $colorLtValue = 1;
            if ($_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::INSET) {
                $colorLtValue = .5;
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

        $dict = $this->getDictionary();
        $value = \SetaPDF_Core_Type_Dictionary_Helper::getValue($dict, 'V', '', true);
        if ($value === '') {
            return;
        }

        $daValues = $this->_getDaValues();

        $font = $this->_appearanceFont ?: $this->getFont();
        $textBlock = new \SetaPDF_Core_Text_Block($font, null);
        $textBlock->setAlign($this->getAlign());

        $borderDoubled = (
            $_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::BEVELED ||
            $_borderStyle === \SetaPDF_Core_Document_Page_Annotation_BorderStyle::INSET
        );

        $offset = \max(1, $borderWidth * ($borderDoubled ? 2 : 1)) * 2;
        $clipOffset = \max(1, $borderWidth * ($borderDoubled ? 2 : 1));

        $canvas->markedContent()->begin('Tx');

        // Clip
        $canvas->path()->rect(
            $clipOffset,
            $clipOffset,
            $width - $clipOffset * 2,
            $height - $clipOffset * 2
        )->clip()->endPath();

        $multiline = $this->isMultiline();

        if ($multiline === false) {
            $value = \str_replace([
                // replace line breaks and tab with spaces
                "\x00\x0d\x00\x0a",
                "\x00\x0d",
                "\x00\x0a",
                // tab to space
                "\x00\x09"
            ], "\x00\x20", $value);
        } else {
            // normalize line breaks and convert tabs to spaces
            $value = \str_replace("\x00\x09", "\x00\x20", \SetaPDF_Core_Text::normalizeLineBreaks($value));
        }

        $textBlock->setText(
            \SetaPDF_Core_Encoding::convertPdfString($value, 'UTF-16BE'),
            'UTF-16BE'
        );
        $textBlock->setPadding($offset);
        $fontSize = $daValues['fontSize']->getValue();

        if ($multiline) {
            $textBlock->setWidth($width - $offset * 2);
        }

        if ($fontSize === 0) {
            $textBlock->setFontSize(12);
            $textWidthAt12Points = $textBlock->getTextWidth();

            $fontSize = ($width - $offset * 2) / $textWidthAt12Points * 12;
            $textBlock->setFontSize($fontSize);

            if (!$multiline) {
                $textHeight = $textBlock->getTextHeight();
                if ($textHeight > ($height - $offset * 2)) {
                    // A near value...
                    $fontSize = \max(4, ($height - $offset * 2) * $fontSize / $textHeight);
                }
            }
        }
        $textBlock->setFontSize($fontSize);
        $textBlock->setTextColor($daValues['color']);

        if ($multiline) {
            $textBlock->draw($canvas, 0, $height - $textBlock->getHeight() - $borderWidth);
        } else {
            $textBlock->draw($canvas, 0, $height / 2 - $textBlock->getHeight() / 2);
        }

        $canvas->markedContent()->end();
    }

    /**
     * @param \SetaPDF_Core_Document|null $document
     * @return \SetaPDF_Core_Type_IndirectObjectInterface
     * @throws \SetaPDF_Core_Exception
     * @throws \SetaPDF_Core_Font_Exception
     * @throws \SetaPDF_Core_SecHandler_Exception
     * @throws \SetaPDF_Core_Type_Exception
     * @throws \SetaPDF_Core_Type_IndirectReference_Exception
     * @throws \SetaPDF_Exception_NotImplemented
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
