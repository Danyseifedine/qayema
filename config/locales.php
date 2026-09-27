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
// second language the owner picks in the dashboard (App\Services\Menu\
// MenuLanguages). Separate from the portal list above on purpose — offering a
// menu in French does not mean translating the whole portal into French.
//
// Adding one here also needs lang/{code}.json with the menu's own words (the
// keys of lang/ar.json). `script` names its writing system in config/fonts.php,
// which is where its fonts come from; languages sharing a script share a font.
// ─────────────────────────────────────────────────────────────────────────────

$menu = [
    'en' => ['name' => 'English',    'english' => 'English',    'flag' => '🇬🇧', 'rtl' => false, 'script' => 'latin'],
    'ar' => ['name' => 'العربية',    'english' => 'Arabic',     'flag' => '🇸🇦', 'rtl' => true,  'script' => 'arabic'],
    'fr' => ['name' => 'Français',   'english' => 'French',     'flag' => '🇫🇷', 'rtl' => false, 'script' => 'latin'],
    'es' => ['name' => 'Español',    'english' => 'Spanish',    'flag' => '🇪🇸', 'rtl' => false, 'script' => 'latin'],
    'tr' => ['name' => 'Türkçe',     'english' => 'Turkish',    'flag' => '🇹🇷', 'rtl' => false, 'script' => 'latin'],
    'de' => ['name' => 'Deutsch',    'english' => 'German',     'flag' => '🇩🇪', 'rtl' => false, 'script' => 'latin'],
    'it' => ['name' => 'Italiano',   'english' => 'Italian',    'flag' => '🇮🇹', 'rtl' => false, 'script' => 'latin'],
    'ru' => ['name' => 'Русский',    'english' => 'Russian',    'flag' => '🇷🇺', 'rtl' => false, 'script' => 'cyrillic'],
    'zh' => ['name' => '中文',        'english' => 'Chinese',    'flag' => '🇨🇳', 'rtl' => false, 'script' => 'chinese'],
    'hi' => ['name' => 'हिन्दी',       'english' => 'Hindi',      'flag' => '🇮🇳', 'rtl' => false, 'script' => 'devanagari'],
    'pt' => ['name' => 'Português',  'english' => 'Portuguese', 'flag' => '🇵🇹', 'rtl' => false, 'script' => 'latin'],
];

return [
    'menu' => $menu,
    'locales' => $locales,
    'supported' => array_keys($locales),
    'rtl' => array_keys(array_filter($locales, fn ($l) => $l['rtl'])),
    'default' => 'en',
];
