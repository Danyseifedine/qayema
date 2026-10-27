<?php

use App\Support\Ingredients;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ingredients written before they were tidied on save ("onion,..tomato...ect")
 * become the same clean list every new save makes. A language left with
 * nothing (only "etc.") is dropped, as a blank one is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('dishes')
            ->whereNotNull('ingredients')
            ->select(['id', 'ingredients'])
            ->orderBy('id')
            ->chunkById(500, function ($dishes): void {
                foreach ($dishes as $dish) {
                    $before = json_decode((string) $dish->ingredients, true);

                    if (! is_array($before)) {
                        continue;
                    }

                    $after = [];
                    foreach ($before as $code => $text) {
                        $tidy = is_string($text) ? Ingredients::tidy($text, (string) $code) : null;
                        if ($tidy !== null) {
                            $after[$code] = $tidy;
                        }
                    }

                    if ($after !== $before) {
                        DB::table('dishes')->where('id', $dish->id)->update([
                            'ingredients' => $after === [] ? null : json_encode($after, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            });
    }

    /** Nothing to undo: the tidy list says the same as what was typed. */
    public function down(): void {}
};
