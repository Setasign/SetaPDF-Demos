<?php

namespace setasign\SetaPDF2\Demos\Signer\Module\Signature;

use setasign\SetaPDF2\Core\Type\PdfDictionary;
use setasign\SetaPDF2\Core\Type\PdfName;
use setasign\SetaPDF2\Signer\Asn1\Element as Asn1Element;
use setasign\SetaPDF2\Signer\Asn1\Oid;
use setasign\SetaPDF2\Signer\Digest;
use setasign\SetaPDF2\Signer\Signature\Module\Pades;

class PadesWithSignaturePolicyModule extends Pades
{
    protected $_policyOid;
    protected $_policySha256Hash;
    protected $_policyUrl;

    public function setSignaturePolicy(string $oid, string $sha256Hash, string $url): void
    {
        $this->_policyOid = $oid;
        $this->_policySha256Hash = $sha256Hash;
        $this->_policyUrl = $url;
    }

    /**
     * @return array|Asn1Element[]|null
     * @throws \setasign\SetaPDF2\Signer\Exception
     */
    protected function _getSignedAttributes()
    {
        $signedAttributes = parent::_getSignedAttributes();
        if (!isset($signedAttributes['1.2.840.113549.1.9.16.2.15']) && $this->_policyOid !== null) {
            $signedAttributes['1.2.840.113549.1.9.16.2.15'] = $this->_getSignaturePolicyIdentifierAttribute();
        }

        return $signedAttributes;
    }

    /**
     * @return Asn1Element
     */
    protected function _getSignaturePolicyIdentifierAttribute(): Asn1Element
    {
        /**
         * signature-policy-identifier attribute:
         * https://www.rfc-editor.org/rfc/rfc5126.html#section-5.8.1
         */
        $sigPolicyQualifiers = new Asn1Element(
            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
            [
                new Asn1Element(
                    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
                    [
                        new Asn1Element(
                            Asn1Element::OBJECT_IDENTIFIER,
                            Oid::encode('1.2.840.113549.1.9.16.5.1') // sigPolicyQualifier-spuri
                        ),
                        new Asn1Element(
                            Asn1Element::IA5_STRING,
                            $this->_policyUrl
                        )
                    ]
                )
            ]
        );

        $signaturePolicyId = new Asn1Element(
            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
            [
                new Asn1Element(
                    Asn1Element::OBJECT_IDENTIFIER,
                    Oid::encode($this->_policyOid)
                ),
                new Asn1Element(
                    Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
                    [
                        new Asn1Element(
                            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
                            [
                                new Asn1Element(
                                    Asn1Element::OBJECT_IDENTIFIER,
                                    Oid::encode(Digest::getOid(Digest::SHA_256))
                                ),
                                new Asn1Element(Asn1Element::NULL)
                            ]
                        ),
                        new Asn1Element(
                            Asn1Element::OCTET_STRING,
                            hex2bin($this->_policySha256Hash)
                        ),
                    ]
                ),
                $sigPolicyQualifiers
            ]
        );

        return new Asn1Element(
            Asn1Element::SEQUENCE | Asn1Element::IS_CONSTRUCTED, '',
            [
                new Asn1Element(
                    Asn1Element::OBJECT_IDENTIFIER,
                    Oid::encode('1.2.840.113549.1.9.16.2.15') // sigPolicyId
                ),
                new Asn1Element(
                    Asn1Element::SET | Asn1Element::IS_CONSTRUCTED, '',
                    [
                        $signaturePolicyId
                    ]
                )
            ]
        );
    }

//    /**
//     * You may overwrite this method to e.g. update the Filter and SubFilter according to ICP Brasil.
//     *
//     * @param PdfDictionary $dictionary
//     * @return void
//     * @throws \setasign\SetaPDF2\Signer\Exception
//     */
//    public function updateSignatureDictionary(PdfDictionary $dictionary)
//    {
//        parent::updateSignatureDictionary($dictionary);
//
//        $dictionary['SubFilter'] = new PdfName('PBAD.PAdES', true);
//        $dictionary['Filter'] = new PdfName('PBAD_PAdES', true);
//    }
}
