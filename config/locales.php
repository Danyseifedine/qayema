<?php

// ─── ADD A NEW LANGUAGE HERE ──────────────────────────────────────────────────
//
// To add a new language to the entire project:
//   1. Add an entry to the $locales array below
//   2. Create  lang/{code}/auth.php  and  lang/{code}/menu_owner.php
//
// Everything else (supported list, RTL detection, all language pickers in the
// dashboard and onboarding) updates automatically — nothing else to touch.
// ─────────────────────────────────────────────────────────────────────────────

$locales = [
    'en' => ['name' => 'English',    'flag' => '🇬🇧', 'rtl' => false],
    'ar' => ['name' => 'العربية',    'flag' => '🇸🇦', 'rtl' => true],
    'fr' => ['name' => 'Français',   'flag' => '🇫🇷', 'rtl' => false],
    'de' => ['name' => 'Deutsch',    'flag' => '🇩🇪', 'rtl' => false],
    'es' => ['name' => 'Español',    'flag' => '🇪🇸', 'rtl' => false],
    'it' => ['name' => 'Italiano',   'flag' => '🇮🇹', 'rtl' => false],
    'pt' => ['name' => 'Português',  'flag' => '🇵🇹', 'rtl' => false],
    'ru' => ['name' => 'Русский',    'flag' => '🇷🇺', 'rtl' => false],
    'tr' => ['name' => 'Türkçe',     'flag' => '🇹🇷', 'rtl' => false],
    'hi' => ['name' => 'हिन्दी',      'flag' => '🇮🇳', 'rtl' => false],
    'zh' => ['name' => '中文',        'flag' => '🇨🇳', 'rtl' => false],
    'ja' => ['name' => '日本語',      'flag' => '🇯🇵', 'rtl' => false],
    'ko' => ['name' => '한국어',      'flag' => '🇰🇷', 'rtl' => false],
    'nl' => ['name' => 'Nederlands',  'flag' => '🇳🇱', 'rtl' => false],
];

return [
    'locales' => $locales,
    'supported' => array_keys($locales),
    'rtl' => array_keys(array_filter($locales, fn ($l) => $l['rtl'])),
    'default' => 'en',
];
