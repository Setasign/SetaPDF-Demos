<?php

use setasign\SetaPDF2\Stamper\Stamper;

return [
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_TOP & 45°',
        'position' => Stamper::POSITION_LEFT_TOP,
        'rotation' => 45
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_TOP & -45°',
        'position' => Stamper::POSITION_RIGHT_TOP,
        'rotation' => -45
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_TOP & 180°',
        'position' => Stamper::POSITION_CENTER_TOP,
        'rotation' => 180
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_MIDDLE & 90°',
        'position' => Stamper::POSITION_LEFT_MIDDLE,
        'rotation' => 90
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_MIDDLE & 25°',
        'position' => Stamper::POSITION_CENTER_MIDDLE,
        'rotation' => 25
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_MIDDLE & -90°',
        'position' => Stamper::POSITION_RIGHT_MIDDLE,
        'rotation' => -90
    ],

    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_LEFT_BOTTOM & 45°',
        'position' => Stamper::POSITION_LEFT_BOTTOM,
        'rotation' => 45
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_RIGHT_BOTTOM & -45°',
        'position' => Stamper::POSITION_RIGHT_BOTTOM,
        'rotation' => -45
    ],
    [
        'displayValue' => '\setasign\SetaPDF2\Stamper\Stamper::POSITION_CENTER_BOTTOM & 180°',
        'position' => Stamper::POSITION_CENTER_BOTTOM,
        'rotation' => 180
    ],
];
