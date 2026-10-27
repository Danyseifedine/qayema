<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Offers section is gone from the menu: offers keep their other
 * places, and one that showed only there becomes a banner.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('offers')->where('placements', 'like', '%"section"%')->get(['id', 'placements']) as $offer) {
            $kept = array_values(array_diff((array) json_decode((string) $offer->placements, true), ['section']));

            DB::table('offers')->where('id', $offer->id)->update(['placements' => json_encode($kept ?: ['banner'])]);
        }
    }

    public function down(): void
    {
        // The section is not coming back with its old offers.
    }
};
