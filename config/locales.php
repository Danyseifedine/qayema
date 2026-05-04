<?php

return [
    /*
     * All locales supported by the owner dashboard.
     * Referenced by SetMenuOwnerLocale middleware and the locale-switch route.
     */
    'supported' => ['en', 'ar', 'fr', 'de', 'es', 'it', 'hi', 'pt', 'ru', 'tr'],

    'default' => 'en',

    /*
     * Locales that require right-to-left layout direction.
     */
    'rtl' => ['ar'],
];
