<?php

return [
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_ALL',
        'showOnPage' => SetaPDF_Stamper::PAGES_ALL
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_EVEN',
        'showOnPage' => SetaPDF_Stamper::PAGES_EVEN
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_ODD',
        'showOnPage' => SetaPDF_Stamper::PAGES_ODD
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_FIRST',
        'showOnPage' => SetaPDF_Stamper::PAGES_FIRST
    ],
    [
        'displayValue' => 'SetaPDF_Stamper::PAGES_LAST',
        'showOnPage' => SetaPDF_Stamper::PAGES_LAST
    ],
    [
        'displayValue' => "'2-' (2nd page until the last page)",
        'showOnPage' => '2-'
    ],
    [
        'displayValue' => "'1-5' (page 1 to 5)",
        'showOnPage' => '1-5'
    ],
    [
        'displayValue' => '[3, 5, 8, 99]',
        'showOnPage' => [3, 5, 8, 99]
    ],
    [
        'displayValue' => 'second last page (callback function)',
        'showOnPage' => function($pageNumber, $pageCount) {
            return $pageNumber === ($pageCount - 1);
        }
    ]
];
