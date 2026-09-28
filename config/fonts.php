<?php

// ─── MENU FONTS ───────────────────────────────────────────────────────────────
//
// The fonts an owner can pick for their menu, one list per writing system
// (script). Every menu language names its script in config('locales.menu'), so
// English and Spanish share the Latin pick and Arabic has its own.
//
// Each family is a Google Font. `weights` are the ones it really has. Google
// answers 400 to a request for a weight a family lacks, which would leave the
// menu in the fallback font. `category` only groups the picker.
//
// `latin_first`: whether Latin text inside this script's menu (prices, brand
// names) uses the owner's Latin pick. True where the script's fonts also carry
// Latin letters that would otherwise win (Arabic, Chinese); false where Latin
// fonts also carry this script and would otherwise hide the pick (Cyrillic,
// Devanagari).
//
// `sample` is a dish name in that script, drawn in each font in the picker.
// ─────────────────────────────────────────────────────────────────────────────

$all = [400, 500, 600, 700];

return [
    'latin' => [
        'default' => 'Inter',
        'latin_first' => true,
        'sample' => 'Grilled halloumi · 12.50',
        'fonts' => [
            'Inter' => ['category' => 'sans', 'weights' => $all],
            'Poppins' => ['category' => 'sans', 'weights' => $all],
            'Montserrat' => ['category' => 'sans', 'weights' => $all],
            'DM Sans' => ['category' => 'sans', 'weights' => $all],
            'Nunito' => ['category' => 'sans', 'weights' => $all],
            'Playfair Display' => ['category' => 'serif', 'weights' => $all],
            'Lora' => ['category' => 'serif', 'weights' => $all],
            'Cormorant Garamond' => ['category' => 'serif', 'weights' => $all],
        ],
    ],

    'arabic' => [
        'default' => 'El Messiri',
        'latin_first' => true,
        'sample' => 'حلوم مشوي بالزعتر',
        'fonts' => [
            'El Messiri' => ['category' => 'display', 'weights' => $all],
            'Cairo' => ['category' => 'sans', 'weights' => $all],
            'Tajawal' => ['category' => 'sans', 'weights' => [400, 500, 700]],
            'Almarai' => ['category' => 'sans', 'weights' => [400, 700]],
            'Readex Pro' => ['category' => 'sans', 'weights' => $all],
            'IBM Plex Sans Arabic' => ['category' => 'sans', 'weights' => $all],
            'Noto Kufi Arabic' => ['category' => 'sans', 'weights' => $all],
            'Amiri' => ['category' => 'serif', 'weights' => [400, 700]],
        ],
    ],

    'cyrillic' => [
        'default' => 'Inter',
        'latin_first' => false,
        'sample' => 'Жареный халуми с тимьяном',
        'fonts' => [
            'Inter' => ['category' => 'sans', 'weights' => $all],
            'Montserrat' => ['category' => 'sans', 'weights' => $all],
            'Roboto' => ['category' => 'sans', 'weights' => $all],
            'Nunito' => ['category' => 'sans', 'weights' => $all],
            'PT Sans' => ['category' => 'sans', 'weights' => [400, 700]],
            'Playfair Display' => ['category' => 'serif', 'weights' => $all],
            'Lora' => ['category' => 'serif', 'weights' => $all],
            'PT Serif' => ['category' => 'serif', 'weights' => [400, 700]],
        ],
    ],

    'chinese' => [
        'default' => 'Noto Sans SC',
        'latin_first' => true,
        'sample' => '香煎哈罗米芝士',
        'fonts' => [
            'Noto Sans SC' => ['category' => 'sans', 'weights' => $all],
            'Noto Serif SC' => ['category' => 'serif', 'weights' => $all],
            'ZCOOL XiaoWei' => ['category' => 'display', 'weights' => [400]],
        ],
    ],

    'devanagari' => [
        'default' => 'Noto Sans Devanagari',
        'latin_first' => false,
        'sample' => 'ग्रिल्ड हलूमी पनीर',
        'fonts' => [
            'Noto Sans Devanagari' => ['category' => 'sans', 'weights' => $all],
            'Poppins' => ['category' => 'sans', 'weights' => $all],
            'Hind' => ['category' => 'sans', 'weights' => $all],
            'Mukta' => ['category' => 'sans', 'weights' => $all],
            'Noto Serif Devanagari' => ['category' => 'serif', 'weights' => $all],
            'Tiro Devanagari Hindi' => ['category' => 'serif', 'weights' => [400]],
        ],
    ],
];
