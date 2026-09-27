<?php

// ─── ADD A NEW LANGUAGE HERE ──────────────────────────────────────────────────
//
// To add a new language to the entire project:
//   1. Add an entry to the $locales array below
//   2. Create  lang/{code}/auth.php  and  lang/{code}/owner.php
//
// Everything else (supported list, RTL detection, all language pickers in the
// dashboard and onboarding) updates automatically — nothing else to touch.
// ─────────────────────────────────────────────────────────────────────────────

$locales = [
    'en' => ['name' => 'English',    'flag' => '🇬🇧', 'rtl' => false],
    'ar' => ['name' => 'العربية',    'flag' => '🇸🇦', 'rtl' => true],
];

// ─── MENU LANGUAGES ───────────────────────────────────────────────────────────
//
// What a restaurant's menu can be written in: English, always, plus at most one
// second language the owner picks in the dashboard (App\Services\Global\
// MenuLanguages). Separate from the portal list above on purpose — offering a
// menu in French does not mean translating the whole portal into French.
//
// Adding one here also needs lang/{code}.json with the menu's own words (the
// keys of lang/ar.json). `font` is a Google Font for scripts Inter does not
// cover; null means Inter.
// ─────────────────────────────────────────────────────────────────────────────

$menu = [
    'en' => ['name' => 'English',    'english' => 'English',    'flag' => '🇬🇧', 'rtl' => false, 'font' => null],
    'ar' => ['name' => 'العربية',    'english' => 'Arabic',     'flag' => '🇸🇦', 'rtl' => true,  'font' => 'El Messiri'],
    'fr' => ['name' => 'Français',   'english' => 'French',     'flag' => '🇫🇷', 'rtl' => false, 'font' => null],
    'es' => ['name' => 'Español',    'english' => 'Spanish',    'flag' => '🇪🇸', 'rtl' => false, 'font' => null],
    'tr' => ['name' => 'Türkçe',     'english' => 'Turkish',    'flag' => '🇹🇷', 'rtl' => false, 'font' => null],
    'de' => ['name' => 'Deutsch',    'english' => 'German',     'flag' => '🇩🇪', 'rtl' => false, 'font' => null],
    'it' => ['name' => 'Italiano',   'english' => 'Italian',    'flag' => '🇮🇹', 'rtl' => false, 'font' => null],
    'ru' => ['name' => 'Русский',    'english' => 'Russian',    'flag' => '🇷🇺', 'rtl' => false, 'font' => null],
    'zh' => ['name' => '中文',        'english' => 'Chinese',    'flag' => '🇨🇳', 'rtl' => false, 'font' => 'Noto Sans SC'],
    'hi' => ['name' => 'हिन्दी',       'english' => 'Hindi',      'flag' => '🇮🇳', 'rtl' => false, 'font' => 'Noto Sans Devanagari'],
    'pt' => ['name' => 'Português',  'english' => 'Portuguese', 'flag' => '🇵🇹', 'rtl' => false, 'font' => null],
];

return [
    'menu' => $menu,
    'locales' => $locales,
    'supported' => array_keys($locales),
    'rtl' => array_keys(array_filter($locales, fn ($l) => $l['rtl'])),
    'default' => 'en',
];
