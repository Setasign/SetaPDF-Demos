<?php

use setasign\SetaPDF2\Stamper\Stamper;

return [
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::PAGES_ALL',
        'showOnPage' => Stamper::PAGES_ALL
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::PAGES_EVEN',
        'showOnPage' => Stamper::PAGES_EVEN
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::PAGES_ODD',
        'showOnPage' => Stamper::PAGES_ODD
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::PAGES_FIRST',
        'showOnPage' => Stamper::PAGES_FIRST
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::PAGES_LAST',
        'showOnPage' => Stamper::PAGES_LAST
    ],
    [
        'displayValue' => '4',
        'showOnPage' => 4
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
