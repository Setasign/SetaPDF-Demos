<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Type\IndirectObjectInterface;
use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfName;
use setasign\SetaPDF2\Core\Type\PdfStream;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Signer\Signature\Module\Pades as PadesModule;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// to get access to the signature dictionary, we extend the PAdES module
class MySignatureModule extends PadesModule
{
    /**
     * @var IndirectObjectInterface
     */
    protected $metadata;

    /**
     * @param IndirectObjectInterface $metadata The indirect object/reference to the metadata stream.
     */
    public function setMetadata(IndirectObjectInterface $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param PdfDictionary $dictionary
     * @throws \setasign\SetaPDF2\Signer\Exception
     */
    public function updateSignatureDictionary(PdfDictionary $dictionary)
    {
        parent::updateSignatureDictionary($dictionary);
        $dictionary->offsetSet('Metadata', $this->metadata);
    }
}

$writer = new HttpWriter('signed.pdf');
$document = Document::loadByFilename(
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report.pdf',
    $writer
);

// create a signer instance
$signer = new Signer($document);
// add a signature field
$field = $signer->addSignatureField();
// and define that you want to use this field
$signer->setSignatureFieldName($field->getQualifiedName());

$certificatePath = $assetsDirectory . '/certificates/setapdf-no-pw.pem';

// now create an instance of the signature module
$module = new MySignatureModule();

// create a metadata object
$metadataObject = $document->createNewObject(new PdfStream(
    new PdfDictionary([
        'Type' => new PdfName('Metadata', true),
        'Subtype' => new PdfName('XML', true)
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
