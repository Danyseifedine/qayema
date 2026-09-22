<?php

namespace App\Services\Global;

use App\Enums\CoinTransactionType;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\TemplatePurchase;
use Illuminate\Support\Facades\DB;

/**
 * Buying templates with coins.
 *
 * Ownership is permanent and recorded once: the coin debit and the
 * `template_purchases` row are written in a single transaction, so a restaurant
 * can never be charged without getting the template, or get it for free.
 */
class TemplateStore
{
    /**
     * Unlock a paid template for a restaurant, spending the owner's coins.
     *
     * Returns the existing purchase untouched when the template is already
     * owned, so a double-clicked button can't charge twice.
     *
     * @throws \App\Exceptions\InsufficientCoins
     */
    public function unlock(Restaurant $restaurant, Template $template): TemplatePurchase
    {
        $existing = $restaurant->templatePurchases()
            ->where('template_id', $template->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($restaurant, $template): TemplatePurchase {
            // Re-check inside the transaction: two requests racing here would
            // otherwise both see "not owned" and both debit.
            $existing = $restaurant->templatePurchases()
                ->where('template_id', $template->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $purchase = $restaurant->templatePurchases()->create([
                'template_id' => $template->id,
                'price_paid' => $template->price,
            ]);

            $transaction = $restaurant->user->wallet()->debit(
                $template->price,
                CoinTransactionType::Spend,
                'template:'.$purchase->id,
                ['template' => $template->slug],
            );

            $purchase->forceFill(['coin_transaction_id' => $transaction->id])->save();

            return $purchase;
        });
    }

    /**
     * Switch the restaurant to a template it already owns, seeding the
     * template's default settings.
     */
    public function select(Restaurant $restaurant, Template $template): void
    {
        $restaurant->update([
            'template_id' => $template->id,
            'template_settings' => $template->defaultSettings(),
        ]);
    }
}
