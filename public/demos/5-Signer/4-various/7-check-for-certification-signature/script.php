<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/camtown/Laboratory-Report-signed.pdf',
    $assetsDirectory . '/pdfs/lenstown/Laboratory-Report-signed-PAdES.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-certified-form-fill-and-sign.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-certified-no-changes-allowed.pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-certified-annotating-form-fill-and-sign.pdf'
];

$file = displayFiles($files, true, false, true);
if (is_array($file)) {
    extract($file);
} else {
    $filename = basename($file);
}

/**
 * A helper function to get the certification level
 *
 * @param SetaPDF_Core_Document $document
 * @return float|int|void
 * @throws SetaPDF_Core_SecHandler_Exception
 * @throws SetaPDF_Core_Type_Exception
 */
function getCertificationLevel(SetaPDF_Core_Document $document) {
    $root = SetaPDF_Core_Type_Dictionary::ensureType($document->getCatalog()->getDictionary());
    $perms = SetaPDF_Core_Type_Dictionary_Helper::getValue($root, 'Perms');
    if (!$perms instanceof SetaPDF_Core_Type_Dictionary) {
        return;
    }

    $docMdp = SetaPDF_Core_Type_Dictionary_Helper::getValue($perms, 'DocMDP');
    if (!$docMdp instanceof SetaPDF_Core_Type_Dictionary) {
        return;
    }

    // ...check if modifications are allowed

    $referenceArray = SetaPDF_Core_Type_Array::ensureType(
        SetaPDF_Core_Type_Dictionary_Helper::getValue(
            $docMdp, 'Reference', new SetaPDF_Core_Type_Array()
        )
    );

    if (count($referenceArray) === 0) {
        return;
    }

    $reference = SetaPDF_Core_Type_Dictionary::ensureType($referenceArray->offsetGet(0));
    $transformMethod = SetaPDF_Core_Type_Dictionary_Helper::getValue($reference, 'TransformMethod');
    if (!$transformMethod instanceof SetaPDF_Core_Type_Name || $transformMethod->getValue() !== 'DocMDP') {
        return;
    }

    $transformParams = SetaPDF_Core_Type_Dictionary_Helper::getValue($reference, 'TransformParams');
    if (!$transformParams instanceof SetaPDF_Core_Type_Dictionary) {
        return;
    }

    // if there is one, check if modifications are allowed
    $p = SetaPDF_Core_Type_Numeric::ensureType(
        SetaPDF_Core_Type_Dictionary_Helper::getValue(
            $transformParams,
            'P',
            new SetaPDF_Core_Type_Numeric(SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING)
        )
    );

    return $p->getValue();
}

try {
    $document = SetaPDF_Core_Document::loadByFilename($file);
    $certficationLevel = getCertificationLevel($document);
    if ($certficationLevel === null) {
        echo "Document is not certified.";
        die();
    }

    echo '<span style="color:#22caff;">Document has a certification signature!</span><br />';

    if ($certficationLevel === SetaPDF_Signer::CERTIFICATION_LEVEL_NO_CHANGES_ALLOWED) {
        echo '<span style="color:red">No changes allowed.</span>';
    } elseif ($certficationLevel === SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING) {
        echo '<span style="color:green">Form filling and signing is allowed.</span>';
    } elseif ($certficationLevel === SetaPDF_Signer::CERTIFICATION_LEVEL_FORM_FILLING_AND_ANNOTATIONS) {
        echo '<span style="color:green">Annotating, form filling and signing is allowed.</span>';
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
