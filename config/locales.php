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
];

return [
    'locales' => $locales,
    'supported' => array_keys($locales),
    'rtl' => array_keys(array_filter($locales, fn ($l) => $l['rtl'])),
    'default' => 'en',
];
