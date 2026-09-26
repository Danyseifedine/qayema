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
    |
    | Mirrored in qayema-dashboard/src/shared/constants/countries.ts.
    |
    */

    'LB' => ['label' => 'Lebanon', 'flag' => '🇱🇧', 'dial' => '+961'],
    'AE' => ['label' => 'United Arab Emirates', 'flag' => '🇦🇪', 'dial' => '+971'],
    'SA' => ['label' => 'Saudi Arabia', 'flag' => '🇸🇦', 'dial' => '+966'],
    'EG' => ['label' => 'Egypt', 'flag' => '🇪🇬', 'dial' => '+20'],
    'JO' => ['label' => 'Jordan', 'flag' => '🇯🇴', 'dial' => '+962'],
    'KW' => ['label' => 'Kuwait', 'flag' => '🇰🇼', 'dial' => '+965'],
    'QA' => ['label' => 'Qatar', 'flag' => '🇶🇦', 'dial' => '+974'],
    'BH' => ['label' => 'Bahrain', 'flag' => '🇧🇭', 'dial' => '+973'],
    'OM' => ['label' => 'Oman', 'flag' => '🇴🇲', 'dial' => '+968'],
    'SY' => ['label' => 'Syria', 'flag' => '🇸🇾', 'dial' => '+963'],
    'IQ' => ['label' => 'Iraq', 'flag' => '🇮🇶', 'dial' => '+964'],
    'TR' => ['label' => 'Turkey', 'flag' => '🇹🇷', 'dial' => '+90'],
    'US' => ['label' => 'United States', 'flag' => '🇺🇸', 'dial' => '+1'],
    'GB' => ['label' => 'United Kingdom', 'flag' => '🇬🇧', 'dial' => '+44'],
    'FR' => ['label' => 'France', 'flag' => '🇫🇷', 'dial' => '+33'],
    'DE' => ['label' => 'Germany', 'flag' => '🇩🇪', 'dial' => '+49'],
    'IT' => ['label' => 'Italy', 'flag' => '🇮🇹', 'dial' => '+39'],
    'ES' => ['label' => 'Spain', 'flag' => '🇪🇸', 'dial' => '+34'],
    'GR' => ['label' => 'Greece', 'flag' => '🇬🇷', 'dial' => '+30'],
    'NL' => ['label' => 'Netherlands', 'flag' => '🇳🇱', 'dial' => '+31'],
    'PT' => ['label' => 'Portugal', 'flag' => '🇵🇹', 'dial' => '+351'],
    'RU' => ['label' => 'Russia', 'flag' => '🇷🇺', 'dial' => '+7'],
    'CN' => ['label' => 'China', 'flag' => '🇨🇳', 'dial' => '+86'],
    'JP' => ['label' => 'Japan', 'flag' => '🇯🇵', 'dial' => '+81'],
    'KR' => ['label' => 'South Korea', 'flag' => '🇰🇷', 'dial' => '+82'],
    'IN' => ['label' => 'India', 'flag' => '🇮🇳', 'dial' => '+91'],
    'AU' => ['label' => 'Australia', 'flag' => '🇦🇺', 'dial' => '+61'],
    'CA' => ['label' => 'Canada', 'flag' => '🇨🇦', 'dial' => '+1'],

];
