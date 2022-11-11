<?php

namespace com\setasign\SetaPDF\Demos\Signer\Module\Signature;

class OpenSslPrivateEncryptModule implements \SetaPDF_Signer_Signature_Module_ModuleInterface,
    \SetaPDF_Signer_Signature_DictionaryInterface,
    \SetaPDF_Signer_Signature_DocumentInterface
{
    use \SetaPDF_Signer_Signature_Module_PadesProxyTrait;

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
        $details = openssl_pkey_get_details($privateKey);
        if (!is_array($details)) {
            throw new \InvalidArgumentException('Cannot get details from private key.');
        }

        if ($details['type'] !== OPENSSL_KEYTYPE_RSA) {
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
     * @param \SetaPDF_Core_Reader_FilePath $tmpPath
     * @return string
     * @throws \SetaPDF_Signer_Exception
     */
    public function createSignature(\SetaPDF_Core_Reader_FilePath $tmpPath)
    {
        $padesModule = $this->_getPadesModule();
        // get the hash data from the module
        $padesDigest = $padesModule->getDigest();

        $hashData = hash($padesDigest, $padesModule->getDataToSign($tmpPath), true);

        // let's sign only the hash, so we create the ASN.1 container manually
        $digestInfo = new \SetaPDF_Signer_Asn1_Element(
            \SetaPDF_Signer_Asn1_Element::SEQUENCE | \SetaPDF_Signer_Asn1_Element::IS_CONSTRUCTED, '',
            [
                new \SetaPDF_Signer_Asn1_Element(
                    \SetaPDF_Signer_Asn1_Element::SEQUENCE | \SetaPDF_Signer_Asn1_Element::IS_CONSTRUCTED, '',
                    [
                        new \SetaPDF_Signer_Asn1_Element(
                            \SetaPDF_Signer_Asn1_Element::OBJECT_IDENTIFIER,
                            \SetaPDF_Signer_Asn1_Oid::encode(
                                \SetaPDF_Signer_Digest::getOid($padesModule->getDigest())
                            )
                        ),
                        new \SetaPDF_Signer_Asn1_Element(\SetaPDF_Signer_Asn1_Element::NULL)
                    ]
                ),
                new \SetaPDF_Signer_Asn1_Element(
                    \SetaPDF_Signer_Asn1_Element::OCTET_STRING,
                    $hashData
                )
            ]
        );

        if (@openssl_private_encrypt($digestInfo, $signatureValue, $this->_privateKey) === false) {
            $lastError = error_get_last();
            throw new \SetaPDF_Signer_Exception(
                'An OpenSSL error occured during signature process' .
                (isset($lastError['message']) ? ': ' . $lastError['message'] : '') . '.'
            );
        }

        $padesModule->setSignatureValue($signatureValue);

        return (string)$padesModule->getCms();
    }
}
