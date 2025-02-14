<?php

namespace com\setasign\SetaPDF\Demos\Annotation\Widget;

use setasign\SetaPDF2\Core\Canvas\Draw;
use setasign\SetaPDF2\Core\DataStructure\Color;
use setasign\SetaPDF2\Core\DataStructure\Color\Gray;
use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Catalog\AcroForm;
use setasign\SetaPDF2\Core\Document\Page\Annotation\AppearanceCharacteristics;
use setasign\SetaPDF2\Core\Document\Page\Annotation\BorderStyle;
use setasign\SetaPDF2\Core\Document\Page\Annotation\Widget;
use setasign\SetaPDF2\Core\Encoding;
use setasign\SetaPDF2\Core\Exception;
use setasign\SetaPDF2\Core\Font;
use setasign\SetaPDF2\Core\Font\FontInterface;
use setasign\SetaPDF2\Core\Parser\Content;
use setasign\SetaPDF2\Core\Resource;
use setasign\SetaPDF2\Core\Text;
use setasign\SetaPDF2\Core\Text\Block;
use setasign\SetaPDF2\Core\Type\AbstractType;
use setasign\SetaPDF2\Core\Type\Dictionary\Helper as DictionaryHelper;
use setasign\SetaPDF2\Core\Type\IndirectObjectInterface;
use setasign\SetaPDF2\Core\Type\PdfArray;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfName;
use setasign\SetaPDF2\Core\Type\PdfNumeric;
use setasign\SetaPDF2\Core\Type\PdfString;
use setasign\SetaPDF2\Core\Writer;
use setasign\SetaPDF2\Core\XObject\Form;

/**
 * Example class representing a push-button.
 */
class Pushbutton extends Widget
{
    /**
     * @var Document
     */
    protected $_document;

    /**
     * Creates a new button field in a specific document
     *
     * @param array|AbstractType|PdfDictionary|IndirectObjectInterface $objectOrDictionary
     * @param $fieldName
     * @param Document $document
     */
    public function __construct($objectOrDictionary, $fieldName, Document $document)
    {
        $this->_document = $document;

        parent::__construct($objectOrDictionary);
        $dict = $this->getDictionary();
        $dict['FT'] = new PdfName('Btn');
        $this->setFieldFlags(0x010000); // pushbutton -> 17

        $acroForm = $document->getCatalog()->getAcroForm();
        $acroForm->addDefaultEntriesAndValues();

        // Ensure unique field name
        $fieldNames = [];
        foreach ($acroForm->getTerminalFieldsObjects() as $terminalObject) {
            /** @var string $name */
            $name = AcroForm::resolveFieldName($terminalObject->ensure());
            $fieldNames[$name] = $name;
        }

        $i = 1;
        $oFieldName = $fieldName;
        /** @var string $fieldName */
        $fieldName = str_replace('.', '_', $fieldName);
        while (isset($fieldNames[$fieldName])) {
            $fieldName = $oFieldName . '_' . ($i++);
        }

        $dict['T'] = new PdfString(Encoding::toPdfString($fieldName));
    }

    /**
     * Set the button caption
     *
     * @param string $caption
     * @param string $encoding
     */
    public function setCaption(string $caption, string $encoding = 'UTF-8')
    {
        /** @var AppearanceCharacteristics $appCharacteristics */
        $appCharacteristics = $this->getAppearanceCharacteristics(true);
        $dict = $appCharacteristics->getDictionary();
        $dict['CA'] = new PdfString(Encoding::toPdfString($caption, $encoding));
    }

    /**
     * Set the font
     *
     * @param FontInterface $font
     * @throws Exception
     */
    public function setFont(FontInterface $font)
    {
        $daValues = $this->_getDaValues();

        $writer = new Writer();
        PdfName::writePdfString($writer, $this->_document->getCatalog()->getAcroForm()->addResource($font));
        $daValues['fontSize']->writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $daValues['color']->draw($writer, false);

        $this->_annotationDictionary['DA'] = new PdfString($writer);
    }

    /**
     * Get the font
     *
     * @return Font
     * @throws Exception
     */
    public function getFont()
    {
        $daValues = $this->_getDaValues();
        $fonts = $this->_document->getCatalog()->getAcroForm()->getDefaultResources(true, Resource::TYPE_FONT);

        return Font::get($fonts->getValue($daValues['fontName']->getValue()));
    }

    /**
     * Set the font size
     *
     * @param int|float $fontSize
     * @throws Exception
     */
    public function setFontSize($fontSize)
    {
        $daValues = $this->_getDaValues();

        $writer = new Writer();
        $daValues['fontSize'] = new PdfNumeric($fontSize);
        PdfName::writePdfString($writer, $daValues['fontName']->getValue());
        PdfNumeric::writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $daValues['color']->draw($writer, false);

        $this->_annotationDictionary['DA'] = new PdfString($writer);
    }

    /**
     * Set the text color
     *
     * @param int|float|string|array|PdfArray|Color $color
     * @throws Exception
     */
    public function setTextColor($color)
    {
        if (!$color instanceof Color) {
            $color = Color::createByComponents($color);
        }

        $daValues = $this->_getDaValues();

        $writer = new Writer();
        PdfName::writePdfString($writer, $daValues['fontName']->getValue());
        PdfNumeric::writePdfString($writer, $daValues['fontSize']->getValue());
        $writer->write(' Tf');
        $color->draw($writer, false);

        $this->_annotationDictionary['DA'] = new PdfString($writer);
    }

    /**
     * Get default appearance values
     *
     * @return array
     * @throws Exception
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
        $parser->registerOperator('Tf', static function($params) use (&$fontName, &$fontSize) {
            $fontName = $params[0];
            $fontSize = $params[1];
        });
        $parser->registerOperator(['g', 'rg', 'k'], static function($params) use (&$color) {
            $color = Color::createByComponents($params);
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
            $colorLtValue = 1; //' 1 g';
            if ($_borderStyle === BorderStyle::INSET) {
                $colorLtValue = .5; // ' 0.5 g';
            }

            /**
             * This color adjustment is not needed for list boxes.
             * The effect will only occur if the field is active
             * All other fields will use this effect.
             */
            if ($_borderStyle === BorderStyle::BEVELED && $backgroundColor) {
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

        $daValues = $this->_getDaValues();

        $font = $this->getFont();
        $textBlock = new Block($font, null);

        $borderDoubled = (
            $_borderStyle === BorderStyle::BEVELED ||
            $_borderStyle === BorderStyle::INSET
        );

        $offset = max(1, $borderWidth * ($borderDoubled ? 2 : 1)) * 2;

        /** @var AppearanceCharacteristics $appCharacteristics */
        $appCharacteristics = $this->getAppearanceCharacteristics(true);
        $dict = $appCharacteristics->getDictionary();
        $textBlock->setText(Encoding::convertPdfString(
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
        $textBlock->setTextWidth($width - $offset * 2);
        $textBlock->setFontSize($fontSize);
        $textBlock->setTextColor($daValues['color']);

        $textBlock->setAlign(Text::ALIGN_CENTER);
        $textBlock->draw($canvas, 0, $height / 2 - $textBlock->getHeight() / 2);
    }

    /**
     * @return IndirectObjectInterface
     */
    public function getIndirectObject(Document $document = null)
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
    public function unsetFieldFlags(int $flags)
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
    public function getFieldFlags(): int
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
    public function isFieldFlagSet(int $flag): bool
    {
        return ($this->getFieldFlags() & $flag) !== 0;
    }
}
