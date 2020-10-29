<?php

// load and register the autoload function
require_once '../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/Brand-Guide.pdf',
    $assetsDirectory . '/pdfs/Brand-Guide-Encrypted (owner-pw setasign).pdf',
    $assetsDirectory . '/pdfs/tektown/Laboratory-Report-up=topsecret,op=owner.pdf'
];

$path = displayFiles($files);

$document = SetaPDF_Core_Document::loadByFilename($path);

if (!$document->hasSecHandler()) {
    echo 'Document is not encrypted.';
    return;
}

$data = [];

$secHandler = $document->getSecHandler();
if ($secHandler instanceof SetaPDF_Core_SecHandler_PublicKey) {
    $data['Security Method'] = 'Public Key Security';

} elseif ($secHandler instanceof SetaPDF_Core_SecHandler_Standard) {
    $data['Security Method'] = 'Password Security';
    $data['Document Open Password'] = ($secHandler->auth() ? 'No' : 'Yes');
    $data['Permissions Password'] = ($secHandler->getAuthMode() === SetaPDF_Core_SecHandler::OWNER ? 'No' : 'Yes');

    if (!$secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_PRINT)) {
        $data['Printing'] = 'No';
    } elseif ($secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_DIGITAL_PRINT)) {
        $data['Printing'] = 'High Resolution';
    } else {
        $data['Printing'] = 'Low Resolution (150 dpi)';
    }

    $modify = $secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_MODIFY);
    $data['Changing the Document'] = $modify ? 'Allowed' : 'Not Allowed';

    $annot = $secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_ANNOT);
    $data['Commenting'] = $modify || $annot ? 'Allowed' : 'Not Allowed';

    $data['Form Field Fill-in or Signing'] =
        (
            $modify
            || $annot
            || $secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_FILL_FORM)
        ) ? 'Allowed' : 'Not Allowed';

    $data['Document Assembly'] =
        ($modify || $secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_ASSEMBLE))
        ? 'Allowed' : 'Not Allowed';

    $copy = $secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_COPY);
    $data['Content Copying'] = $copy ? 'Allowed' : 'Not Allowed';

    $data['Content Accessibility Enabled'] =
        ($copy || $secHandler->getPermission(SetaPDF_Core_SecHandler::PERM_ACCESSIBILITY))
        ? 'Allowed' : 'Not Allowed';

    $data['Page Extraction'] =
        $secHandler->getAuthMode() === SetaPDF_Core_SecHandler::OWNER ? 'Allowed' : 'Not Allowed';

    list($algorithm, $keyLength) = $secHandler->getStringAlgorithm();
    if ($algorithm & SetaPDF_Core_SecHandler::ARCFOUR) {
        $data['Encryption Level'] = ($keyLength * 8) . '-bit RC4';
    } elseif ($algorithm & SetaPDF_Core_SecHandler::AES) {
        $data['Encryption Level'] = ($keyLength * 8) . '-bit AES';
    }
}

?>
<table>
    <?php foreach($data as $label => $value):?>
        <tr>
            <td><?=htmlspecialchars($label);?>:</td>
            <td><?=htmlspecialchars($value);?></td>
        </tr>
    <?php endforeach;?>
</table>
