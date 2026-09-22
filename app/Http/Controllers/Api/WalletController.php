<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinPack;
use App\Models\CoinTransaction;
use App\Services\Global\PaddlePrices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The owner's coin balance, their ledger, and the packs they can top up with.
 */
class WalletController extends Controller
{
    /**
     * Balance plus the ledger, newest first, paginated (`?page=`, 25 per page).
     * Each purchase row carries the Paddle transaction id so the SPA can link
     * to its invoice.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $page = $user->coinTransactions()
            ->latest('id')
            ->paginate(perPage: 25, page: max(1, (int) $request->query('page', 1)));

        return response()->json([
            'data' => [
                'balance' => $user->wallet()->balance(),
                'transactions' => collect($page->items())
                    ->map(fn (CoinTransaction $transaction): array => [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'balance_after' => $transaction->balance_after,
                        'type' => $transaction->type->value,
                        'label' => $transaction->type->label(),
                        'reference' => $transaction->reference,
                        'created_at' => $transaction->created_at?->toIso8601String(),
                    ])
                    ->values(),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * The purchasable coin packs with their live Paddle amounts. A pack whose
     * amount couldn't be fetched is returned with a null price so the SPA can
     * show it as unavailable rather than with a stale number.
     */
    public function packs(Request $request, PaddlePrices $prices): JsonResponse
    {
        $amounts = $prices->amounts();

        $data = CoinPack::query()->sellable()->get()
            ->map(fn (CoinPack $pack): array => [
                'id' => $pack->id,
                'slug' => $pack->slug,
                'name' => [
                    'en' => $pack->getTranslation('name', 'en', false) ?: null,
                    'ar' => $pack->getTranslation('name', 'ar', false) ?: null,
                ],
                'coins' => $pack->coins,
                'amount' => $amounts[$pack->slug]['amount'] ?? null,
                'currency' => $amounts[$pack->slug]['currency'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $data]);
    }
}
