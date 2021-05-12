<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// to get access to the signature dictionary, we extend the PAdES module
class MySignatureModule extends SetaPDF_Signer_Signature_Module_Pades
{
    /**
     * @var SetaPDF_Core_Type_IndirectObjectInterface
     */
    protected $metadata;

    /**
     * @param SetaPDF_Core_Type_IndirectObjectInterface $metadata The indirect object/reference to the metadata stream.
     */
    public function setMetadata(SetaPDF_Core_Type_IndirectObjectInterface $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param SetaPDF_Core_Type_Dictionary $dictionary
     * @throws SetaPDF_Signer_Exception
     */
    public function updateSignatureDictionary(SetaPDF_Core_Type_Dictionary $dictionary)
    {
        parent::updateSignatureDictionary($dictionary);
        $dictionary->offsetSet('Metadata', $this->metadata);
    }
}

$writer = new SetaPDF_Core_Writer_Http('signed.pdf');
$document = SetaPDF_Core_Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new SetaPDF_Signer($document);
// add a signature field
$field = $signer->addSignatureField();
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create an instance of the signature module
$module = new MySignatureModule();

// create a metadata object
$metadataObject = $document->createNewObject(new SetaPDF_Core_Type_Stream(
    new SetaPDF_Core_Type_Dictionary([
        'Type' => new SetaPDF_Core_Type_Name('Metadata', true),
        'Subtype' => new SetaPDF_Core_Type_Name('XML', true)
    ]),
    '<?xpacket begin="' . "\xEF\xBB\xBF" . '" id="W5M0MpCehiHzreSzNTczkc9d"?>' . "\n" .
    '<x:xmpmeta xmlns:x="adobe:ns:meta/"><!-- here goes your XMP package --></x:xmpmeta>' . "\n" .
    '<?xpacket end="w"?>'
));

// and pass a reference to it to the
$module->setMetadata($metadataObject);

// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// sign the document with the module
$signer->sign($module);
