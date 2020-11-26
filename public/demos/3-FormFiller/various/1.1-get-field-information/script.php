<?php

// load and register the autoload function
require_once __DIR__ . '/../../../../../bootstrap.php';

$files = [
    $assetsDirectory . '/pdfs/tektown/Order-Form-with-money-fields.pdf',
    $assetsDirectory . '/pdfs/tektown/Subscription-tekMag.pdf',
    $assetsDirectory . '/pdfs/forms/Customizer-Example.pdf',
    $assetsDirectory . '/pdfs/forms/Sunnysunday-Example.pdf',
    $assetsDirectory . '/pdfs/etown/Terms-and-Conditions.pdf',
];

$path = displayFiles($files);

// a simple helper function to output a table
function drawPropertyTable($caption, $data) {
    echo '<table border="1" width="100%"><caption>' . htmlspecialchars($caption) . '</caption>';
    echo '<colgroup><col width="33%" /><col width="67%" /></colgroup>';
    foreach ($data as $key => $value) {
        echo '<tr><th>' . htmlspecialchars($key) . ':</th>';
        echo '<td><pre>' . htmlspecialchars(print_r($value, true)) . '</pre></td></tr>';
    }
    echo '</table>';
}

// create the document instance
$document = SetaPDF_Core_Document::loadByFilename($path);

// now get an instance of the form filler
$formFiller = new SetaPDF_FormFiller($document);

// Get the form fields of the document
$fields = $formFiller->getFields();

echo '<h1>' . basename($path) . '</h1>';
echo '<p>Field count: ' . count($fields) . '</p>';

// walk trough the fields
foreach ($fields AS $name => $field) {
    $type = get_class($field);

    echo '<h2>Fieldname: ' . htmlspecialchars($name);
        // Check for the real name (suffixed if several fields with the same name exists)
        if ($field->getOriginalQualifiedName() !== $name) {
            echo ' (' . htmlspecialchars($field->getOriginalQualifiedName()) . ')';
        }
    echo '</h2>';

    drawPropertyTable('Standard Properties', [
        'Type' => $type,
        'Page Number' => $field->getPageNumber(),
        'Read-only' => ($field->isReadOnly() ? 'Yes' : 'No'),
        'Required' => ($field->isRequired() ? 'Yes' : 'No'),
        'Is "No Export" flag set' => ($field->getNoExport() ? 'Yes' : 'No')
    ]);

    $typeProps = [];
    if (method_exists($field, 'getAdditionalActions')) {
        $additionalActions = $field->getAdditionalActions();

        $allAdditionalActions = [
            'calculate' => $additionalActions->getCalculate(),
            'keystroke' => $additionalActions->getKeystroke(),
            'format' => $additionalActions->getFormat(),
            'validate' => $additionalActions->getValidate()
        ];
        $allAdditionalActions = array_filter($allAdditionalActions);

        if (count($allAdditionalActions) > 0) {
            $propValue = [];
            foreach ($allAdditionalActions as $actionName => $action) {
                $propValue[$actionName] = $action->getJavaScript();
            }

            $typeProps['Additional Actions'] = print_r($propValue, true);
        }
    }

    switch ($type) {
        // Button / Checkbox
        case SetaPDF_FormFiller_Field_Button::class:
            /** @var SetaPDF_FormFiller_Field_Button $field */
            $typeProps['Rect'] = $field->getAnnotation()->getRect()->toPhp();
            $typeProps['Default Value'] = $field->getDefaultValue();
            $typeProps['Checked'] = ($field->isChecked() ? 'Yes' : 'No');
            $typeProps['Export Value'] = $field->getExportValue();
            break;
        // Radio buttons
        case SetaPDF_FormFiller_Field_ButtonGroup::class:
            /** @var SetaPDF_FormFiller_Field_ButtonGroup $field */
            $typeProps['Value'] = $field->getValue();
            $typeProps['Default Value'] = $field->getDefaultValue();

            // Get the buttons, related to this group
            $buttons = $field->getButtons();
            $propValue = [];
            foreach ($buttons AS $button) {
                /** @var SetaPDF_FormFiller_Field_Button $button */
                $propValue[$button->getQualifiedName()] = [
                    'Checked' => $button->isChecked() ? 'Yes' : 'No',
                    'Export Value' => $button->getExportValue(),
                    'Rect' => $button->getAnnotation()->getRect()->toPhp()
                ];
            }

            $typeProps['Buttons'] = print_r($propValue, true);
            break;

        // List field
        case SetaPDF_FormFiller_Field_List::class:
            /** @var SetaPDF_FormFiller_Field_List $field */
            $typeProps['Rect'] = $field->getAnnotation()->getRect()->toPhp();
            $typeProps['Is multi-select'] = ($field->isMultiSelect() ? 'Yes' : 'No');
            $typeProps['Default Value'] = print_r($field->getDefaultValue(), true);
            $typeProps['Value'] = print_r($field->getValue(), true);
            $typeProps['Visible Value'] = print_r($field->getVisibleValue(), true);
            $typeProps['Options'] = print_r($field->getOptions(), true);
            break;

        // Combo Box / Select field
        case SetaPDF_FormFiller_Field_Combo::class:
            /** @var SetaPDF_FormFiller_Field_Combo $field */
            $typeProps['Rect'] = $field->getAnnotation()->getRect()->toPhp();
            $typeProps['Is editable'] = ($field->isEditable() ? 'Yes' : 'No');
            $typeProps['Default Value'] = $field->getDefaultValue();
            $typeProps['Value'] = $field->getValue();
            $typeProps['Visible Value'] = $field->getVisibleValue();
            $typeProps['Options'] = print_r($field->getOptions(), true);
            break;

        // Text field
        case SetaPDF_FormFiller_Field_Text::class:
            /** @var SetaPDF_FormFiller_Field_Text $field */
            $typeProps['Rect'] = $field->getAnnotation()->getRect()->toPhp();
            $typeProps['Max Length'] = $field->getMaxLength();
            $typeProps['Multiline'] = ($field->isMultiline() ? 'Yes' : 'No');
            $typeProps['Comb Field'] = ($field->isCombField() ? 'Yes' : 'No');
            $typeProps['Password Field'] = ($field->isPasswordField() ? 'Yes' : 'No');
            $typeProps['Is "DoNotSpellCheck" flag set'] = ($field->isDoNotSpellCheckSet() ? 'Yes' : 'No');
            $typeProps['Is "DoNotScroll" flag set'] = ($field->isDoNotScrollSet() ? 'Yes' : 'No');
            $typeProps['Default Value'] = $field->getDefaultValue();
            $typeProps['Value'] = $field->getValue();
            break;
    }

    if (count($typeProps) > 0) {
        drawPropertyTable('Type related properties', $typeProps);
    }
}
