<?php

namespace setasign\SetaPDF2\Demos\Signer\Module\Signature;

use setasign\SetaPDF2\Core\Reader\FilePath;
use setasign\SetaPDF2\Signer\Asn1\Element as Asn1Element;
use setasign\SetaPDF2\Signer\Asn1\Oid as Asn1Oid;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\Exception;
use setasign\SetaPDF2\Signer\Signature\Module\DictionaryInterface;
use setasign\SetaPDF2\Signer\Signature\Module\DocumentInterface;
use setasign\SetaPDF2\Signer\Signature\Module\ModuleInterface;
use setasign\SetaPDF2\Signer\Signature\Module\PadesProxyTrait;

class OpenSslPrivateEncryptModule implements ModuleInterface, DictionaryInterface, DocumentInterface
{
    use PadesProxyTrait;

    /**
     * @var \OpenSSLAsymmetricKey|resource
     */
    protected $_privateKey;

    /**
     * @param \OpenSSLAsymmetricKey|resource $privateKey
     * @return void
     */
    public function setPrivateKey($privateKey)
    {
        $details = \openssl_pkey_get_details($privateKey);
        if (!is_array($details)) {
            throw new \InvalidArgumentException('Cannot get details from private key.');
        }

        if ($details['type'] !== \OPENSSL_KEYTYPE_RSA) {
            throw new \InvalidArgumentException('Only RSA keys are supported in this demo.');
        }

        $this->_privateKey = $privateKey;
    }

    /**
     * @param string $digest
     * @return void
     */
    public function setDigest($digest)
    {
        $this->_getPadesModule()->setDigest($digest);
    }

    /**
     * @param FilePath $tmpPath
     * @return string
     * @throws Exception
     */
    public function createSignature(FilePath $tmpPath)
    {
        $padesModule = $this->_getPadesModule();
        // get the hash data from the module
        $padesDigest = $padesModule->getDigest();

        $hashData = hash($padesDigest, $padesModule->getDataToSign($tmpPath), true);

        // let's sign only the hash, so we create the ASN.1 container manually
        $digestInfo = new Asn1Element(
            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
            [
                new Asn1Element(
                    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
                    [
                        new Asn1Element(
                            Asn1Element::OBJECT_IDENTIFIER,
                            Asn1Oid::encode(
                                Digest::getOid($padesModule->getDigest())
                            )
                        ),
                        new Asn1Element(Asn1Element::NULL)
                    ]
                ),
                new Asn1Element(
                    Asn1Element::OCTET_STRING,
                    $hashData
                )
            ]
        );

        if (@\openssl_private_encrypt($digestInfo, $signatureValue, $this->_privateKey) === false) {
            $lastError = \error_get_last();
            throw new Exception(
                'An OpenSSL error occurred during signature process' .
                (isset($lastError['message']) ? ': ' . $lastError['message'] : '') . '.'
            );
        }

        $padesModule->setSignatureValue($signatureValue);

        return (string)$padesModule->getCms();
    }
}
