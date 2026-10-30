<?php

namespace App\Services\Pos;

use App\Jobs\SyncPosCatalog;
use App\Models\PosConnection;
use App\Models\Restaurant;
use App\Services\Menu\MenuCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Linking a restaurant to a POS branch and ending it. Linking checks the
 * key with the POS, that both price in the same main currency and that the
 * branch is not already another menu's, then gives the POS the webhook
 * address and a fresh signing secret, and pulls the catalog. Ending it
 * tells the POS (if it answers) and hands the menu back to the owner: the
 * dishes the POS made stay, editable again.
 */
class PosLink
{
    public function __construct(private readonly MenuCache $menus) {}

    public function link(Restaurant $restaurant, string $typedAddress, string $key): PosConnection
    {
        $address = PosAddress::normalize($typedAddress);

        if ($address === null) {
            throw ValidationException::withMessages(['address' => __('Enter your POS address as it appears in your POS, starting with https://.')]);
        }

        $client = new PosClient($address, trim($key));

        try {
            $info = $client->connection();
        } catch (PosException $exception) {
            throw ValidationException::withMessages([$exception->status === 401 ? 'key' : 'address' => $exception->getMessage()]);
        }

        if (! is_string($info['id'] ?? null) || ! Str::isUuid($info['id'])) {
            throw ValidationException::withMessages(['address' => __('No POS answers at this address.')]);
        }

        if (($info['currency'] ?? null) !== $restaurant->currency) {
            throw ValidationException::withMessages(['key' => __('Your POS prices in :pos but your menu is in :menu. Set the same main currency on both.', ['pos' => (string) ($info['currency'] ?? '?'), 'menu' => $restaurant->currency])]);
        }

        if (PosConnection::query()->where('pos_uuid', $info['id'])->where('restaurant_id', '!=', $restaurant->id)->exists()) {
            throw ValidationException::withMessages(['key' => __('This key is already linked to another menu.')]);
        }

        $secret = Str::random(48);

        try {
            $client->link(route('api.pos.webhook'), $secret);
        } catch (PosException $exception) {
            throw ValidationException::withMessages(['key' => $exception->getMessage()]);
        }

        $connection = DB::transaction(fn (): PosConnection => PosConnection::query()->updateOrCreate(['restaurant_id' => $restaurant->id], [
            'address' => $address,
            'key' => trim($key),
            'signing_secret' => $secret,
            'pos_uuid' => $info['id'],
            'company' => is_string($info['company'] ?? null) ? Str::limit($info['company'], 250, '') : null,
            'branch' => is_string($info['branch'] ?? null) ? Str::limit($info['branch'], 250, '') : null,
            'currency' => $info['currency'],
            'locales' => array_values(array_filter((array) ($info['locales'] ?? []), 'is_string')),
        ]));

        $restaurant->setRelation('posConnection', $connection);
        $this->menus->forget($restaurant);
        SyncPosCatalog::dispatch($restaurant->id)->afterResponse();

        return $connection;
    }

    /**
     * Ends the link. The POS is told so it stops calling, but a POS that
     * does not answer never keeps the owner linked.
     */
    public function unlink(Restaurant $restaurant, bool $tellPos = true): void
    {
        $connection = $restaurant->posConnection;

        if ($connection === null) {
            return;
        }

        if ($tellPos) {
            try {
                PosClient::for($connection)->unlink();
            } catch (PosException) {
                // Its key may already be revoked there; the link ends here anyway.
            }
        }

        $connection->delete();
        $restaurant->unsetRelation('posConnection');
        $this->menus->forget($restaurant);
    }
}
