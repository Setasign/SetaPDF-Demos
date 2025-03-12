<?php

use setasign\SetaPDF2\Stamper\Stamper;

return [
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_TOP & $translateX = 20, $translateY = -20',
        'position' => Stamper::POSITION_LEFT_TOP,
        'translateX' => 20,
        'translateY' => -20
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_TOP & $translateX = 0, $translateY = -20',
        'position' => Stamper::POSITION_CENTER_TOP,
        'translateX' => 0,
        'translateY' => -20
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_TOP & $translateX = -20, $translateY = -20',
        'position' => Stamper::POSITION_RIGHT_TOP,
        'translateX' => -20,
        'translateY' => -20
    ],

    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_MIDDLE & $translateX = 20, $translateY = 0',
        'position' => Stamper::POSITION_LEFT_MIDDLE,
        'translateX' => 20,
        'translateY' => 0
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_MIDDLE & $translateX = -20, $translateY = 0',
        'position' => Stamper::POSITION_RIGHT_MIDDLE,
        'translateX' => -20,
        'translateY' => 0
    ],

    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_BOTTOM & $translateX = 20, $translateY = 20',
        'position' => Stamper::POSITION_LEFT_BOTTOM,
        'translateX' => 20,
        'translateY' => 20
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_BOTTOM & $translateX = 0, $translateY = 20',
        'position' => Stamper::POSITION_CENTER_BOTTOM,
        'translateX' => 0,
        'translateY' => 20
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_BOTTOM & $translateX = -20, $translateY = 20',
        'position' => Stamper::POSITION_RIGHT_BOTTOM,
        'translateX' => -20,
        'translateY' => 20
    ],
];
