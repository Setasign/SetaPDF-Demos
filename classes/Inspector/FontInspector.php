<?php

namespace com\setasign\SetaPDF\Demos\Inspector;

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Document\Page;
use setasign\SetaPDF2\Core\Font;
use setasign\SetaPDF2\Core\Resource;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfStream;
use setasign\SetaPDF2\Core\XObject;
use setasign\SetaPDF2\Core\XObject\Form;
use setasign\SetaPDF2\Exception\NotImplemented;

/**
 * Class FontInspector
 */
class FontInspector
{
    /**
     * @var Document
     */
    protected $_document;

    /**
     * All found font references
     */
    public $fonts = [];

    /**
     * All object ids of visited XObjects
     *
     * @var array
     */
    protected $_xObjectObjectIds = [];

    /**
     * The constructor
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->_document = Document::loadByFilename($path);
    }

    /**
     * Resolves all indirect objects of fonts in the document
     *
     * @return array
     */
    public function resolveFonts()
    {
        $pages = $this->_document->getCatalog()->getPages();
        for ($pageNo = 1, $pageCount = $pages->count(); $pageNo <= $pageCount; $pageNo++) {
            $page = $pages->getPage($pageNo);
            $this->_resolveFonts($page);

            // Fonts from annotations / appearance streams
            $annotations = $page->getAnnotations()->getAll();
            foreach ($annotations AS $annotation) {
                $dict = $annotation->getDictionary();
                $ap = $dict->getValue('AP');
                if ($ap === null) {
                    continue;
                }

                foreach ($ap AS $type => $value) {
                    $object = $value->ensure();
                    if ($object instanceof PdfStream) {
                        $this->_resolveFonts($annotation->getAppearance($type));

                    } elseif ($object instanceof PdfDictionary) {
                        foreach ($object AS $subType => $subValue) {
                            $subObject = $subValue->ensure();
                            if ($subObject instanceof PdfStream) {
                                $this->_resolveFonts($annotation->getAppearance($type, $subType));
                            }
                        }
                    }
                }
            }
        }

        // DR entry in AcroForm dictionary
        $acroForm = $this->_document->getCatalog()->getAcroForm();
        $dict = $acroForm->getDictionary(false);
        if ($dict) {
            $dr = $dict->getValue('DR');
            if ($dr && $dr->ensure()->offsetExists('Font')) {
                $fonts = $dr->ensure()->getValue('Font')->ensure();
                $this->_remFonts($fonts);
            }
        }

        return $this->fonts;
    }

    /**
     * Walks through a dictionary and saves the found font object references
     *
     * @param PdfDictionary $fonts
     */
    protected function _remFonts(PdfDictionary $fonts)
    {
        foreach ($fonts AS $fontIndirectObject) {
            $key = $fontIndirectObject->getObjectId() . '-' . $fontIndirectObject->getGen();
            if (isset($this->fonts[$key])) {
                continue;
            }

            $this->fonts[$key] = $fontIndirectObject;
        }
    }

    /**
     * Resolves the fonts of a page or x-object
     *
     * @param Page|Form $object
     */
    protected function _resolveFonts($object)
    {
        $fonts = $object->getCanvas()->getResources(true, false, Resource::TYPE_FONT);
        if ($fonts) {
            $this->_remFonts($fonts);
        }

        $xObjects = $object->getCanvas()->getResources(true, false, Resource::TYPE_X_OBJECT);
        if (!$xObjects) {
            return;
        }

        foreach ($xObjects AS $xObjectIndirectObject) {
            if (isset($this->_xObjectObjectIds[$xObjectIndirectObject->getObjectId()])) {
                continue;
            }

            $dict = $xObjectIndirectObject->ensure()->getValue();
            if ($dict->getValue('Subtype')->getValue() !== 'Form') {
                continue;
            }

            $this->_xObjectObjectIds[$xObjectIndirectObject->getObjectId()] = true;

            $xObject = XObject::get($xObjectIndirectObject);
            $this->_resolveFonts($xObject);
        }
    }

    /**
     * Checks if a font program is embedded
     *
     * @param Font $font
     * @return bool
     * @throws NotImplemented
     */
    public function isFontEmbedded(Font $font)
    {
        $dict = $font->getIndirectObject($this->_document)->ensure();

        switch ($font->getType()) {
            case 'Type0':
                $descendantFonts = $dict->getValue('DescendantFonts');
                if ($descendantFonts === null) {
                    return false;
                }

                $descendantFonts = $descendantFonts->ensure();
                // PDF supports only a single descendant, which shall be a CIDFont.
                $cidfont = $descendantFonts->offsetGet(0);
                if (!$cidfont) {
                    return false;
                }

                $dict = $cidfont->ensure();
                // fall through
            case 'Type1':
            case 'TrueType':
            case 'MMType1':
                $fontDescriptor = $dict->getValue('FontDescriptor');
                if ($fontDescriptor === null) {
                    return false;
                }

                $fontDescriptor = $fontDescriptor->ensure();

                foreach (['FontFile', 'FontFile2', 'FontFile3'] AS $key) {
                    $fontFile = $fontDescriptor->getValue($key);
                    if ($fontFile && $fontFile->ensure() instanceof PdfStream)
                        return true;
                }

                return false;
            case 'Type3':
                throw new NotImplemented('Type3 fonts are not supported.');
        }
    }
}
