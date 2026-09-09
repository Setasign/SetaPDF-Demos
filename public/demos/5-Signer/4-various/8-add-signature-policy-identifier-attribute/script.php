<?php

use setasign\SetaPDF2\Core\Document;
use setasign\SetaPDF2\Core\Writer\HttpWriter;
use setasign\SetaPDF2\Demos\Signer\Module\Signature\PadesWithSignaturePolicyModule;
use setasign\SetaPDF2\Signer\Signer;

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

// load the module class
require_once __DIR__ . '/../../../../../classes/Signer/Module/Signature/PadesWithSignaturePolicyModule.php';

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

// now create a signature module
$module = new PadesWithSignaturePolicyModule();
// pass the path to the certificate
$module->setCertificate('file://' . $certificatePath);
// or its content
//$module->setCertificate(file_get_contents($certificatePath));
// or a certificate instance
//$certificate = \setasign\SetaPDF2\Signer\X509\Certificate::fromFileOrString($certificatePath);
//$module->setCertificate($certificate);
// set the path to the private key (in this demo the key is also saved in the certificate file)
$module->setPrivateKey('file://' . $certificatePath, '');

// All available policies for ICP Brazil can be found e.g. here:
//   https://www.gov.br/iti/pt-br/assuntos/repositorio/artefatos-de-assinatura-digital
$module->setSignaturePolicy(
    '2.16.76.1.7.1.11.1.3',
    '23E4BE4B9B362172E4EBB0E72B86A133ECE5AAD843D8651C6E38A0BA3F08FC60',
    'http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RB_v1_3.der'
);

// sign the document with the module
$signer->sign($module);
