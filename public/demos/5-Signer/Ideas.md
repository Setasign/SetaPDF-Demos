
## Standard Signatures
- [x] ~~Create and Add a Signature Field~~
- [x] ~~Create a Simple Digital Signature~~
- [x] ~~Use a PFX/P12 File~~
- [x] ~~Create a Certification Signature~~
- [x] ~~Create a Timestamp Signature~~
- [x] ~~Create a Digital Signature With a Timestamp~~
- [x] ~~Async Signature With Timestamp~~
  - [ ] Add Link To "Async Signature With External Implementation"
- [x] ~~Several Signatures~~

## Visible Signatures
- [x] ~~Dynamic Appearance~~
- [x] ~~Dynamic Appearance with Background and Graphic~~
- [x] ~~Localized Dynamic Appearance (DynamicVisibleSignatureWithGermanShowTemplates.php)~~
- [x] ~~Image as Appearance~~
- [x] ~~PDF Page as Appearance~~
- [x] ~~Individual Signature Appearance~~
- [x] ~~Several Visible Signatures~~
- [x] ~~Async Visible Signature With Timestamp~~
- [x] ~~Use an Existing Signature Field~~

## Validation Related Information / Long Term Validation
- [ ] In CMS Container
- [ ] In Document Security Store
- [ ] In CMS Container and Document Security Store
- [x] ~~Add VRI to an Existing PDF Document~~

## Various
- [x] ~~Get Existing Signature Fields~~
- [x] ~~Sign an Encrypted PDF Document~~
- [x] ~~Encrypt and Sign~~
- [x] ~~Validation (Proof of Concept)~~
- [x] ~~Get Signed Version~~
- [ ] Stamp annotation on all pages including actions to the last page with the real signature + closing signature (emulation of several appearances)
- [ ] Prepend a cover page which holds the signature fields. Duplicates of the appearance are used in stamp appearances in the document.
- [ ] Use Link annotations as place-holder for the signature field 

## External Implementations/Modules
- [ ] Async Signature With External Implementation
- [ ] External implementation + Timestamp
- [ ] Fortify
  - [x] ~~Web-component~~ 
    - [ ] LTV
  - [x] ~~Vanilla JavaScript~~
- [ ] OpenSSL CLI
  - [x] ~~dgst~~
  - [x] ~~dgst-pss~~
  - [x] ~~pkeyutl~~
  - [x] ~~pkeyutl-capi-engine~~
  - [x] ~~rsautl~~
- [ ] PHP
  - [x] ~~openssl_private_encrypt()~~
  - [ ] phpseclib
- [ ] GlobalSign DSS
  - [ ] Signature + Timestamp
  - [ ] Document Level Timestamp
- [ ] Azure Key Vault
  - [ ] Simple Signature
- [ ] Swisscom AIS
  - [ ] Simple Signature
  - [ ] Document Level Timestamp
  - [ ] Batch Process