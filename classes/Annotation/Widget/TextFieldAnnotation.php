<?php

namespace setasign\SetaPDF2\Demos\Annotation\Widget;

use setasign\SetaPDF2\Core\Canvas\Draw;
use setasign\SetaPDF2\Core\DataStructure\Color\AbstractColor;
use setasign\SetaPDF2\Core\DataStructure\Color\Gray;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Catalog\AcroForm;
use setasign\SetaPDF2\Core\Document\Page\Annotation\BorderStyle;
use setasign\SetaPDF2\Core\Document\Page\Annotation\WidgetAnnotation;
use setasign\SetaPDF2\Core\Encoding\Encoding;
use setasign\SetaPDF2\Core\Exception;
use setasign\SetaPDF2\Core\Font\Font;
use setasign\SetaPDF2\Core\Font\FontInterface;
use setasign\SetaPDF2\Core\Font\Simple;
use setasign\SetaPDF2\Core\Parser\Content;
use setasign\SetaPDF2\Core\Resource\ResourceInterface;
use setasign\SetaPDF2\Core\Text\Text;
use setasign\SetaPDF2\Core\Text\TextBlock;
use setasign\SetaPDF2\Core\Type\AbstractType;
use setasign\SetaPDF2\Core\Type\Dictionary\DictionaryHelper;
use setasign\SetaPDF2\Core\Type\IndirectObjectInterface;
use setasign\SetaPDF2\Core\Type\PdfArray;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfIndirectReference;
use setasign\SetaPDF2\Core\Type\PdfName;
use setasign\SetaPDF2\Core\Type\PdfNumeric;
use setasign\SetaPDF2\Core\Type\PdfString;
use setasign\SetaPDF2\Core\Writer\Writer;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\NotImplementedException;

/**
 * Example class representing a text field.
 */
class TextFieldAnnotation extends WidgetAnnotation
{
    /**
     * @var Document
     */
    protected $_document;

    /**
     * @var Font
     */
    protected $_appearanceFont;

    /**
     * @var string
     */
    protected $_qualifiedName;

    /**
     * Creates a new text field in a specific document
     *
     * @param array|AbstractType|PdfDictionary|IndirectObjectInterface $objectOrDictionary
     * @param string $fieldName
     * @param Document $document
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     * @throws \setasign\SetaPDF2\Core\Type\Exception
     * @throws \setasign\SetaPDF2\Core\Type\IndirectReference\Exception
     */
    public function __construct($objectOrDictionary, $fieldName, Document $document)
    {
        $this->_document = $document;

        parent::__construct($objectOrDictionary);
        $dict = $this->getDictionary();
        $dict->offsetSet('FT', new PdfName('Tx'));
        $this->setPrintFlag();

        $acroForm = $document->getCatalog()->getAcroForm();
        $acroForm->addDefaultEntriesAndValues();

        // Ensure unique field name
        $fieldNames = [];
        foreach ($acroForm->getTerminalFieldsObjects() as $terminalObject) {
            $name = AcroForm::resolveFieldName($terminalObject->ensure());
            $fieldNames[$name] = $name;
        }

        $i = 1;
        $fieldName = \str_replace('.', '_', $fieldName);
        $oFieldName = $fieldName;
        while (isset($fieldNames[$fieldName])) {
            $fieldName = $oFieldName . '_' . ($i++);
        }

        $this->_qualifiedName = $fieldName;
        $dict->offsetSet('T', new PdfString(Encoding::toPdfString($fieldName)));
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
    public function setValue(string $value, string $encoding = 'UTF-8')
    {
        $dict = $this->getDictionary();
        $dict->offsetSet('V', new PdfString(Encoding::toPdfString($value, $encoding)));
    }

    /**
     * @param array $daValues
     */
    protected function _setDaValues(array $daValues)
    {
        $writer = new Writer();
        PdfName::writePdfString($writer, $daValues['fontName']->getValue());
        PdfNumeric::writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $daValues['color']->draw($writer, false);

        $dict = $this->getDictionary();
        $dict->offsetSet('DA', new PdfString($writer));
    }

    /**
     * Set the font
     *
     * @param Simple $font
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     * @throws Exception
     */
    public function setFont(Simple $font)
    {
        $daValues = $this->_getDaValues();
        $daValues['fontName'] = new PdfName(
            $this->_document->getCatalog()->getAcroForm()->addResource($font)
        );
        $this->_setDaValues($daValues);
    }

    /**
     * Set an individual which is used for rendering of the field value.
     *
     * @param FontInterface $appearanceFont
     * @return void
     */
    public function setAppearanceFont(FontInterface $appearanceFont)
    {
        $this->_appearanceFont = $appearanceFont;
    }

    /**
     * Get the font
     *
     * @return Font
     * @throws Exception
     * @throws \setasign\SetaPDF2\Core\Font\Exception
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     * @throws \setasign\SetaPDF2\Core\Type\Exception
     * @throws \setasign\SetaPDF2\Core\Type\IndirectReference\Exception
     * @throws NotImplementedException
     */
    public function getFont()
    {
        $daValues = $this->_getDaValues();
        $fonts = $this->_document->getCatalog()->getAcroForm()->getDefaultResources(true, ResourceInterface::TYPE_FONT);

        /** @var PdfIndirectReference $font */
        $font = $fonts->getValue($daValues['fontName']->getValue());
        return Font::get($font);
    }

    /**
     * Set the font size
     *
     * @param int|float $fontSize
     * @throws Exception
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     */
    public function setFontSize($fontSize)
    {
        $daValues = $this->_getDaValues();
        $daValues['fontSize'] = new PdfNumeric($fontSize);
        $this->_setDaValues($daValues);
    }

    /**
     * Set the text color
     *
     * @param AbstractColor|int|float|string|array|PdfArray $color
     * @throws Exception
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     */
    public function setTextColor($color)
    {
        if (!$color instanceof AbstractColor) {
            $color = AbstractColor::createByComponents($color);
        }

        $daValues = $this->_getDaValues();
        $daValues['color'] = $color;
        $this->_setDaValues($daValues);
    }

    /**
     * Set the form of quadding (justification / align) that shall be used in displaying the fields text.
     *
     * @see Text::ALIGN_LEFT
     * @see Text::ALIGN_CENTER
     * @see Text::ALIGN_RIGHT
     * @param $align
     */
    public function setAlign($align)
    {
        $allowed = [
            Text::ALIGN_LEFT,
            Text::ALIGN_CENTER,
            Text::ALIGN_RIGHT
        ];

        if (!\in_array($align, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid align parameter "' . $align . '".');
        }

        $this->_annotationDictionary->offsetSet('Q', new PdfNumeric(\array_search($align, $allowed, true)));
    }

    /**
     * Get the form of quadding (justification / align) that shall be used in displaying the fields text.
     *
     * @return mixed|string
     */
    public function getAlign()
    {
        $align = DictionaryHelper::getValue($this->getDictionary(), 'Q');
        if (!$align instanceof PdfNumeric) {
            return Text::ALIGN_LEFT;
        }

        $align = (int)$align->getValue();
        $values = [
            Text::ALIGN_LEFT,
            Text::ALIGN_CENTER,
            Text::ALIGN_RIGHT
        ];

        if (!isset($values[$align])) {
            return Text::ALIGN_LEFT;
        }

        return $values[$align];
    }

    /**
     * Check if the multiline flag is set.
     *
     * @return bool
     */
    public function isMultiline(): bool
    {
        return $this->isFieldFlagSet(0x1000);
    }

    /**
     * Set the multiline flag.
     *
     * @param bool $multiline
     */
    public function setMultiline(bool $multiline = true)
    {
        $this->setFieldFlags(0x1000, $multiline);
    }

    /**
     * Get default appearance values
     *
     * @return array
     * @throws Exception
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     */
    protected function _getDaValues()
    {
        $da = DictionaryHelper::resolveAttribute($this->_annotationDictionary, 'DA');
        $da = $da ?: DictionaryHelper::resolveAttribute(
            $this->_document->getCatalog()->getAcroForm()->getDictionary(),
            'DA'
        );

        if (!$da) {
            throw new Exception('No DA key found.');
        }

        $fontName = $fontSize = $color = null;
        $parser = new Content($da->getValue());
        $parser->registerOperator('Tf', function($params) use (&$fontName, &$fontSize) {
            $fontName = $params[0];
            $fontSize = $params[1];
        });
        $parser->registerOperator(['g', 'rg', 'k'], function($params) use (&$color) {
            $color = AbstractColor::createByComponents($params);
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
     *
     * @throws Exception
     * @throws \setasign\SetaPDF2\Core\Font\Exception
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     * @throws \setasign\SetaPDF2\Core\Type\Exception
     * @throws \setasign\SetaPDF2\Core\Type\IndirectReference\Exception
     * @throws NotImplementedException
     */
    protected function _createAppearance()
    {
        $document = $this->_document;

        $width = $this->getWidth();
        $height = $this->getHeight();

        $n = $this->getAppearance('N');
        if (!$n) {
            $n = Form::create($document, [0, 0, $width, $height]);
        }
        $this->setAppearance($n);

        $canvas = $n->getCanvas();

        $appearanceCharacteristics = $this->getAppearanceCharacteristics();
        $borderStyle = $this->getBorderStyle();
        $borderWidth = 0;
        $_borderStyle = BorderStyle::SOLID;

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

            $n->getObject()->ensure()->getValue()->offsetSet('Matrix', new PdfArray([
                new PdfNumeric($a),
                new PdfNumeric($b),
                new PdfNumeric($c),
                new PdfNumeric($d),
                new PdfNumeric($e),
                new PdfNumeric($f)
            ]));
        }

        // Draw Background
        $backgroundColor = $appearanceCharacteristics
            ? $appearanceCharacteristics->getBackgroundColor()
            : null;
        if ($backgroundColor) {
            $backgroundColor->draw($canvas, false);
            $canvas->draw()->rect(0, 0, $width, $height, Draw::STYLE_FILL);
        }

        // Draw Border:
        $borderColor = $appearanceCharacteristics
            ? $appearanceCharacteristics->getBorderColor()
            : null;

        // It is possible to have no border but only a border style!

        // Beveld or Inset
        if ($_borderStyle === BorderStyle::BEVELED ||
            $_borderStyle === BorderStyle::INSET) {
            $colorLtValue = 1;
            if ($_borderStyle === BorderStyle::INSET) {
                $colorLtValue = .5;
            }

            /**
             * This color adjustment is not needed for list boxes.
             * The effect will only occur if the field is active
             * All other fields will use this effect.
             */
            if (
                $_borderStyle === BorderStyle::BEVELED && $backgroundColor
            ) {
                $tmpColor = clone $backgroundColor;
                $tmpColor->adjustAllComponents(-0.250977);
                $colorRb = $tmpColor;
            } else {
                $colorRb = new Gray(.75);
            }

            // Draw the inner border
            $canvas->saveGraphicState();  // q
            Gray::writePdfString($canvas, $colorLtValue, false);

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
            if ($_borderStyle === BorderStyle::DASHED) {
                $canvas->path()->setDashPattern($borderStyle->getDashPattern());
            }

            // Draw border
            // NOT underline
            if ($_borderStyle !== BorderStyle::UNDERLINE) {
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
        $value = DictionaryHelper::getValue($dict, 'V', '', true);
        if ($value === '') {
            return;
        }

        $daValues = $this->_getDaValues();

        $font = $this->_appearanceFont ?: $this->getFont();
        $textBlock = new TextBlock($font, null);
        $textBlock->setAlign($this->getAlign());

        $borderDoubled = (
            $_borderStyle === BorderStyle::BEVELED ||
            $_borderStyle === BorderStyle::INSET
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
            $value = \str_replace("\x00\x09", "\x00\x20", Text::normalizeLineBreaks($value));
        }

        $textBlock->setText(
            Encoding::convertPdfString($value, 'UTF-16BE'),
            'UTF-16BE'
        );
        $textBlock->setPadding($offset);
        $fontSize = $daValues['fontSize']->getValue();

        if ($multiline) {
            $textBlock->setTextWidth($width - $offset * 2);
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
            switch ($textBlock->getAlign()) {
                case Text::ALIGN_CENTER:
                    $left = (($width - $offset * 2) / 2) - ($textBlock->getTextWidth() / 2);
                    break;
                case Text::ALIGN_RIGHT:
                    $left = ($width - $offset * 2) - $textBlock->getTextWidth();
                    break;
                default:
                    $left = 0;
            }
            $textBlock->draw($canvas, $left, $height / 2 - $textBlock->getHeight() / 2);
        }

        $canvas->markedContent()->end();
    }

    /**
     * @param Document|null $document
     * @return IndirectObjectInterface
     * @throws Exception
     * @throws \setasign\SetaPDF2\Core\Font\Exception
     * @throws \setasign\SetaPDF2\Core\SecHandler\Exception
     * @throws \setasign\SetaPDF2\Core\Type\Exception
     * @throws \setasign\SetaPDF2\Core\Type\IndirectReference\Exception
     * @throws NotImplementedException
     */
    public function getIndirectObject(?Document $document = null)
    {
        $this->_createAppearance();
        return parent::getIndirectObject($document);
    }

    /**
     * Sets a field flag
     *
     * @param int $flags
     * @param bool|null $add Add = true, remove = false, set = null
     */
    public function setFieldFlags($flags, $add = true)
    {
        if ($add === false) {
            $this->unsetFieldFlags($flags);
            return;
        }

        $dict = DictionaryHelper::resolveDictionaryByAttribute($this->_annotationDictionary, 'Ff');

        if ($dict instanceof AbstractType) {
            $value = $dict->ensure()->getValue('Ff');
            if ($add === true) {
                $value->setValue($value->getValue() | $flags);
            } else {
                $value->setValue($flags);
            }

        } else {
            $this->_annotationDictionary->offsetSet('Ff', new PdfNumeric($flags));
        }
    }

    /**
     * Removes a field flag
     *
     * @param int $flags
     */
    public function unsetFieldFlags($flags)
    {
        $dict = DictionaryHelper::resolveDictionaryByAttribute($this->_annotationDictionary, 'Ff');

        if ($dict instanceof AbstractType) {
            $value = $dict->ensure()->getValue('Ff');
            $value->setValue($value->getValue() & ~$flags);
        }
    }

    /**
     * Returns the current field flags
     *
     * @return int
     */
    public function getFieldFlags()
    {
        $fieldFlags = DictionaryHelper::resolveAttribute($this->_annotationDictionary, 'Ff');
        if ($fieldFlags) {
            return $fieldFlags->getValue();
        }

        return 0;
    }

    /**
     * Checks if a specific field flag is set
     *
     * @param int $flag
     * @return bool
     */
    public function isFieldFlagSet($flag)
    {
        return ($this->getFieldFlags() & $flag) !== 0;
    }
}
