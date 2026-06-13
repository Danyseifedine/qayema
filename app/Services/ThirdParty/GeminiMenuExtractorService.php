<?php

namespace App\Services\ThirdParty;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiMenuExtractorService
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Extract menu data from an image stored on disk.
     *
     * Returns a normalised array:
     * {
     *   "categories": [
     *     { "name": string, "dishes": [{ "name": string, "price": float|null, "ingredients": string|null }] }
     *   ]
     * }
     *
     * @throws RuntimeException with a user-friendly message on any failure
     */
    public function extractFromPath(string $absolutePath): array
    {
        if (! $this->apiKey) {
            throw new RuntimeException('Gemini API key is not configured. Add GEMINI_API_KEY to your .env file.');
        }

        if (! file_exists($absolutePath)) {
            throw new RuntimeException('Image file not found. Please upload the image again.');
        }

        $base64 = base64_encode(file_get_contents($absolutePath));
        $mimeType = mime_content_type($absolutePath) ?: 'image/jpeg';

        try {
            $response = Http::timeout(60)
                ->retry(2, 5000, throw: false)
                ->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
                    $this->buildPayload($base64, $mimeType)
                );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new RuntimeException('Could not reach the Gemini API. Check your internet connection and try again.');
        }

        return $this->parseResponse($response);
    }

    private function parseResponse(\Illuminate\Http\Client\Response $response): array
    {
        // Rate limited
        if ($response->status() === 429) {
            throw new RuntimeException('Gemini API rate limit reached. Please try again in a few minutes.');
        }

        // Auth failure — bad key
        if ($response->status() === 400 || $response->status() === 403) {
            Log::error('Gemini auth/bad-request error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Gemini API rejected the request. The API key may be invalid.');
        }

        // Any other non-2xx
        if (! $response->successful()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('Gemini API returned an error ('.$response->status().'). Please try again.');
        }

        $json = $response->json();

        // Safety filter or empty response
        $finishReason = data_get($json, 'candidates.0.finishReason');
        if ($finishReason === 'SAFETY') {
            throw new RuntimeException('The image was blocked by Gemini\'s safety filters. Please use a clear menu photo.');
        }

        if (empty(data_get($json, 'candidates'))) {
            throw new RuntimeException('Gemini returned no results. The image may be unreadable or unsupported.');
        }

        $text = data_get($json, 'candidates.0.content.parts.0.text');

        if (! $text) {
            throw new RuntimeException('Gemini returned an empty response. Please try a different image.');
        }

        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini JSON parse error', ['text' => $text]);
            throw new RuntimeException('Could not parse the extracted menu data. Please try again.');
        }

        if (! array_key_exists('categories', $data)) {
            Log::error('Gemini missing categories key', ['data' => $data]);
            throw new RuntimeException('Gemini response was missing expected data. Please try again.');
        }

        return $this->normalise($data);
    }

    private function normalise(array $data): array
    {
        $categories = [];

        foreach ((array) $data['categories'] as $category) {
            $categoryName = trim($category['name'] ?? '');

            if ($categoryName === '') {
                continue;
            }

            $dishes = [];

            foreach ((array) ($category['dishes'] ?? []) as $dish) {
                $dishName = trim($dish['name'] ?? '');

                if ($dishName === '') {
                    continue;
                }

                $price = isset($dish['price']) && is_numeric($dish['price']) && $dish['price'] > 0
                    ? round((float) $dish['price'], 2)
                    : null;

                $ingredients = isset($dish['ingredients']) ? trim($dish['ingredients']) : null;
                if ($ingredients === '') {
                    $ingredients = null;
                }

                $dishes[] = [
                    'name' => $dishName,
                    'price' => $price,
                    'ingredients' => $ingredients,
                ];
            }

            $categories[] = [
                'name' => $categoryName,
                'dishes' => $dishes,
            ];
        }

        return ['categories' => $categories];
    }

    private function buildPayload(string $base64, string $mimeType): array
    {
        return [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64,
                            ],
                        ],
                        [
                            'text' => 'Extract all categories and dishes from this menu image. For each dish extract its name, price as a number (null if not shown), and any description or ingredients as a single plain text string (null if none). Group dishes under their category.',
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'response_schema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'categories' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'name' => ['type' => 'STRING'],
                                    'dishes' => [
                                        'type' => 'ARRAY',
                                        'items' => [
                                            'type' => 'OBJECT',
                                            'properties' => [
                                                'name' => ['type' => 'STRING'],
                                                'price' => ['type' => 'NUMBER'],
                                                'ingredients' => ['type' => 'STRING'],
                                            ],
                                            'required' => ['name'],
                                        ],
                                    ],
                                ],
                                'required' => ['name', 'dishes'],
                            ],
                        ],
                    ],
                    'required' => ['categories'],
                ],
            ],
        ];
    }
}
