<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Countries
    |--------------------------------------------------------------------------
    |
    | The countries a restaurant can pick for its phone number, keyed by the
    | ISO-3166 alpha-2 code stored in `restaurants.country_code`. `dial` is what
    | turns that national number into the international one a WhatsApp link
    | needs, so this list is the single source for both the picker and the link.
    | `currency` is the money people pay in there: onboarding suggests it as
    | the second currency of a menu priced in dollars.
    |
    | Mirrored in qayema-dashboard/src/shared/constants/countries.ts.
    |
    */

    'LB' => ['label' => 'Lebanon', 'flag' => '🇱🇧', 'dial' => '+961', 'currency' => 'LBP'],
    'AE' => ['label' => 'United Arab Emirates', 'flag' => '🇦🇪', 'dial' => '+971', 'currency' => 'AED'],
    'SA' => ['label' => 'Saudi Arabia', 'flag' => '🇸🇦', 'dial' => '+966', 'currency' => 'SAR'],
    'EG' => ['label' => 'Egypt', 'flag' => '🇪🇬', 'dial' => '+20', 'currency' => 'EGP'],
    'JO' => ['label' => 'Jordan', 'flag' => '🇯🇴', 'dial' => '+962', 'currency' => 'JOD'],
    'KW' => ['label' => 'Kuwait', 'flag' => '🇰🇼', 'dial' => '+965', 'currency' => 'KWD'],
    'QA' => ['label' => 'Qatar', 'flag' => '🇶🇦', 'dial' => '+974', 'currency' => 'QAR'],
    'BH' => ['label' => 'Bahrain', 'flag' => '🇧🇭', 'dial' => '+973', 'currency' => 'BHD'],
    'OM' => ['label' => 'Oman', 'flag' => '🇴🇲', 'dial' => '+968', 'currency' => 'OMR'],
    'SY' => ['label' => 'Syria', 'flag' => '🇸🇾', 'dial' => '+963', 'currency' => 'SYP'],
    'IQ' => ['label' => 'Iraq', 'flag' => '🇮🇶', 'dial' => '+964', 'currency' => 'IQD'],
    'TR' => ['label' => 'Turkey', 'flag' => '🇹🇷', 'dial' => '+90', 'currency' => 'TRY'],
    'US' => ['label' => 'United States', 'flag' => '🇺🇸', 'dial' => '+1', 'currency' => 'USD'],
    'GB' => ['label' => 'United Kingdom', 'flag' => '🇬🇧', 'dial' => '+44', 'currency' => 'GBP'],
    'FR' => ['label' => 'France', 'flag' => '🇫🇷', 'dial' => '+33', 'currency' => 'EUR'],
    'DE' => ['label' => 'Germany', 'flag' => '🇩🇪', 'dial' => '+49', 'currency' => 'EUR'],
    'IT' => ['label' => 'Italy', 'flag' => '🇮🇹', 'dial' => '+39', 'currency' => 'EUR'],
    'ES' => ['label' => 'Spain', 'flag' => '🇪🇸', 'dial' => '+34', 'currency' => 'EUR'],
    'GR' => ['label' => 'Greece', 'flag' => '🇬🇷', 'dial' => '+30', 'currency' => 'EUR'],
    'NL' => ['label' => 'Netherlands', 'flag' => '🇳🇱', 'dial' => '+31', 'currency' => 'EUR'],
    'PT' => ['label' => 'Portugal', 'flag' => '🇵🇹', 'dial' => '+351', 'currency' => 'EUR'],
    'RU' => ['label' => 'Russia', 'flag' => '🇷🇺', 'dial' => '+7', 'currency' => 'RUB'],
    'CN' => ['label' => 'China', 'flag' => '🇨🇳', 'dial' => '+86', 'currency' => 'CNY'],
    'JP' => ['label' => 'Japan', 'flag' => '🇯🇵', 'dial' => '+81', 'currency' => 'JPY'],
    'KR' => ['label' => 'South Korea', 'flag' => '🇰🇷', 'dial' => '+82', 'currency' => 'KRW'],
    'IN' => ['label' => 'India', 'flag' => '🇮🇳', 'dial' => '+91', 'currency' => 'INR'],
    'AU' => ['label' => 'Australia', 'flag' => '🇦🇺', 'dial' => '+61', 'currency' => 'AUD'],
    'CA' => ['label' => 'Canada', 'flag' => '🇨🇦', 'dial' => '+1', 'currency' => 'CAD'],

];
