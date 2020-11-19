<?php

return [
    'displayValue' => 'lenstown/Order-Form-without-Signaturefield.pdf',
    'file' => $assetsDirectory . '/pdfs/lenstown/Order-Form-without-Signaturefield.pdf',
    'values' => [
        'Order Number' => '987654',
        'Date' => date('Y-m-d'),
        'Name' => 'Test Person',
        'Company Name' => 'Awesome Company',
        'Adress' => 'Examplestreet 1',
        'City' => 'Exampleria',
        'Zip Code' => '12345',
        'State' => '-',
        'Country' => 'Orasiania',
        'Phone' => '+12 3456 7890',

        'Name_2' => 'Another Test Person',
        'Company Name_2' => 'A more Awesome Company',
        'Adress_2' => 'Examplestreet 2',
        'City_2' => 'Exampleria',
        'Zip Code_2' => '12345',
        'State_2' => '-',
        'Country_2' => 'Orasiania',
        'Phone_2' => '+12 7890 3456',

        'Item-Number.0' => 'X1245',
        'Description.0' => 'Rotator',
        'Quantity.0' => '10',
        'Unit-Price.0' => '15.00 €',
        'Amount.0' => '150.00 €',

        'Item-Number.1' => 'X4567',
        'Description.1' => 'Migrator',
        'Quantity.1' => '5',
        'Unit-Price.1' => '25.00 €',
        'Amount.1' => '125.00 €',

        'Subtotal' => '275.00 €',
        'Tax' => '52.25 €',
        'Freight Cost' => '10.00 €',
        'Total Amount' => '337.25 €'
    ]
];
