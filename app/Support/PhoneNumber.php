<?php

namespace App\Support;

/**
 * A phone number in international form: digits only, the country's dial code
 * in front, no trunk zero from the national format. What wa.me wants, and
 * what a guest's number is stored as (with a "+").
 *
 * Dial codes come from config/countries.php, the same list the pickers use.
 */
final class PhoneNumber
{
    /** The longest number E.164 allows, dial code included. */
    private const MAX_DIGITS = 15;

    /**
     * Longer than this, a number that starts with its country's code was
     * typed with it: national numbers run to 10 digits in nearly every
     * country (an Indian or a US mobile is 10).
     */
    private const LONGEST_NATIONAL = 10;

    /** Where the leading zero is part of the number abroad too. */
    private const KEEPS_LEADING_ZERO = ['IT', 'SM', 'VA'];

    /**
     * An international number back into the guest's form: the country whose
     * code it starts with (the longest that fits) and the rest.
     *
     * @return array{country: ?string, national: string}
     */
    public static function split(string $international): array
    {
        $digits = preg_replace('/\D+/', '', $international) ?? '';
        $best = null;
        $bestDial = '';

        foreach ((array) config('countries') as $code => $country) {
            $dial = preg_replace('/\D+/', '', (string) ($country['dial'] ?? '')) ?? '';

            if ($dial !== '' && str_starts_with($digits, $dial) && strlen($dial) > strlen($bestDial)) {
                $best = (string) $code;
                $bestDial = $dial;
            }
        }

        return ['country' => $best, 'national' => $best === null ? $digits : substr($digits, strlen($bestDial))];
    }

    /**
     * A number as it was typed, into international digits.
     *
     * Written internationally ("+961 70…", "00961 70…") it is taken as it
     * is. Otherwise it is the country's national number and gets the dial
     * code, unless it already starts with that code and is too long to be a
     * national number: an Indian mobile is 10 digits and may itself begin
     * with 91, so starting with the code alone proves nothing.
     */
    public static function international(?string $country, ?string $number): ?string
    {
        $typed = trim((string) $number);
        $national = preg_replace('/\D+/', '', $typed) ?? '';

        if ($national === '') {
            return null;
        }

        $dial = preg_replace('/\D+/', '', (string) config("countries.{$country}.dial", '')) ?? '';

        if ($dial === '') {
            // Without a country we can only trust a number that already looks
            // international; guessing a dial code would message a stranger.
            return strlen($national) > 9 && strlen($national) <= self::MAX_DIGITS ? $national : null;
        }

        if (str_starts_with($typed, '+') || str_starts_with($national, '00')) {
            $international = str_starts_with($typed, '+') ? $national : substr($national, 2);

            return self::fits($international) ? $international : null;
        }

        // A number typed in national form often keeps its trunk zero, which is
        // never part of the international number; Italy's stays (+39 06…).
        if (! in_array($country, self::KEEPS_LEADING_ZERO, true)) {
            $national = ltrim($national, '0');
        }

        $international = str_starts_with($national, $dial) && strlen($national) > self::LONGEST_NATIONAL
            ? $national
            : $dial.$national;

        return self::fits($international) ? $international : null;
    }

    private static function fits(string $international): bool
    {
        return $international !== '' && strlen($international) <= self::MAX_DIGITS;
    }
}
