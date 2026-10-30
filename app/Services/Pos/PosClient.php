<?php

namespace App\Services\Pos;

use App\Models\PosConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Qayema's calls to a POS (its /api/v1/menu API): who a key belongs to,
 * finishing and ending a link, and the catalog. Every answer comes in the
 * POS's envelope ({success, message, data}); anything else, or no answer
 * in time, is a PosException with a message for the owner.
 */
class PosClient
{
    private const CONNECT_SECONDS = 5;

    private const TIMEOUT_SECONDS = 20;

    public function __construct(private readonly string $address, private readonly string $key) {}

    public static function for(PosConnection $connection): self
    {
        return new self($connection->address, $connection->key);
    }

    /**
     * Who the key belongs to: id, status, company, branch, currency, locales
     * and contract_version, as the POS sent them (checked by the caller).
     *
     * @return array<string, mixed>
     */
    public function connection(): array
    {
        return $this->call(fn (PendingRequest $http): Response => $http->get('connection'));
    }

    /**
     * @return array<string, mixed>
     */
    public function link(string $webhookUrl, string $signingSecret): array
    {
        return $this->call(fn (PendingRequest $http): Response => $http->post('connection', ['webhook_url' => $webhookUrl, 'signing_secret' => $signingSecret]));
    }

    public function unlink(): void
    {
        $this->call(fn (PendingRequest $http): Response => $http->delete('connection'));
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return $this->call(fn (PendingRequest $http): Response => $http->timeout(60)->get('catalog'));
    }

    /**
     * Places an order (the same reference twice is one order): its reference,
     * status and the POS's number for it.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function placeOrder(array $order): array
    {
        return $this->call(fn (PendingRequest $http): Response => $http->post('orders', $order));
    }

    /**
     * @param  callable(PendingRequest): Response  $send
     * @return array<string, mixed>
     */
    private function call(callable $send): array
    {
        if (! PosAddress::mayCall($this->address)) {
            throw new PosException(__('This POS address cannot be reached from Qayema.'), retry: false);
        }

        $http = Http::baseUrl($this->address.'/api/v1/menu/')
            ->withToken($this->key)
            ->acceptJson()
            ->withoutRedirecting()
            ->connectTimeout(self::CONNECT_SECONDS)
            ->timeout(self::TIMEOUT_SECONDS);

        try {
            $response = $send($http);
        } catch (ConnectionException) {
            throw new PosException(__('Your POS did not answer. Check its address and that it is online.'));
        }

        $body = $response->json();

        if ($response->successful() && is_array($body) && ($body['success'] ?? false) === true) {
            return is_array($body['data'] ?? null) ? $body['data'] : [];
        }

        throw match (true) {
            $response->status() === 401 => new PosException(__('Your POS refused the key. It may have been revoked: make a new one in your POS.'), retry: false, status: 401),
            $response->status() === 403 => new PosException(is_array($body) && is_string($body['message'] ?? null) ? $body['message'] : __('Your POS refused the call.'), retry: false, status: 403),
            $response->status() === 404 => new PosException(__('No POS answers at this address.'), retry: false, status: 404),
            $response->status() === 429 => new PosException(__('Your POS asked to slow down. Try again in a minute.'), status: 429),
            $response->status() === 422 => new PosException(self::refusal($body), retry: false, status: 422),
            default => new PosException(__('Your POS could not answer right now. Try again later.'), status: $response->status()),
        };
    }

    /**
     * What the POS said when it refused: its message and each line it named.
     */
    private static function refusal(mixed $body): string
    {
        if (! is_array($body)) {
            return __('Your POS refused it.');
        }

        $lines = array_filter(array_map(fn (mixed $line): ?string => is_array($line) && is_string($line['message'] ?? null) ? $line['message'] : null, (array) ($body['data']['lines'] ?? [])));
        $message = is_string($body['message'] ?? null) ? $body['message'] : __('Your POS refused it.');

        return Str::limit(trim($message.' '.implode(' ', $lines)), 480);
    }
}
