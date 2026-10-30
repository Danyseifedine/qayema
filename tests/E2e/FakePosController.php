<?php

namespace Tests\E2e;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A POS for the Playwright suite's Connect to POS tests: its menu API
 * (/api/v1/menu) at http://127.0.0.1:8001/e2e/fake-pos, answering with the
 * contract samples both repos commit (tests/Fixtures/pos-contract). Every
 * key starting "menu_" works, and each key is its own branch (its own id),
 * so tests running side by side never share a link. Its photo is served
 * here too, so a sync never leaves the machine. It takes orders as W-0001,
 * and refuses one whose note says REFUSE.
 */
class FakePosController extends Controller
{
    public function connection(Request $request): JsonResponse
    {
        return $this->answer($request, fn (string $id): array => [...$this->fixture('connection')['data'], 'id' => $id, 'status' => 'pending']);
    }

    public function link(Request $request): JsonResponse
    {
        return $this->answer($request, fn (string $id): array => [...$this->fixture('connection')['data'], 'id' => $id, 'status' => 'linked']);
    }

    public function unlink(Request $request): JsonResponse
    {
        return $this->answer($request, fn (): null => null);
    }

    public function catalog(Request $request): JsonResponse
    {
        return $this->answer($request, function (): array {
            $catalog = $this->fixture('catalog')['data'];

            foreach ($catalog['products'] as $index => $product) {
                if (is_array($product['image'] ?? null)) {
                    $catalog['products'][$index]['image']['url'] = url('/e2e/fake-pos/photo.png');
                }
            }

            return $catalog;
        });
    }

    /**
     * An order from the menu: taken as W-0001, or refused the way the POS
     * refuses a line it no longer sells when the guest's note says REFUSE.
     */
    public function order(Request $request): JsonResponse
    {
        if (str_contains((string) $request->input('note'), 'REFUSE')) {
            return response()->json([
                'success' => false,
                'message' => 'Some items in this order are no longer sold.',
                'errors' => (object) [],
                'code' => 'lines_unavailable',
                'data' => ['lines' => [['index' => 0, 'product_id' => '301', 'message' => '"Hummus" is no longer sold.']]],
            ], 422);
        }

        return $this->answer($request, fn (): array => [
            'reference' => (string) $request->input('reference'),
            'status' => 'received',
            'pos_number' => 'W-0001',
            'reason' => null,
        ], 201);
    }

    public function photo(): Response
    {
        $image = imagecreatetruecolor(240, 240);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 214, 170, 84));
        ob_start();
        imagepng($image);

        return response((string) ob_get_clean(), 200, ['Content-Type' => 'image/png']);
    }

    /**
     * The POS's envelope around what the key may see; a missing or foreign
     * key is refused as the POS refuses it.
     *
     * @param  callable(string): mixed  $data  given the key's branch id
     */
    private function answer(Request $request, callable $data, int $status = 200): JsonResponse
    {
        $key = (string) $request->bearerToken();

        if (! str_starts_with($key, 'menu_')) {
            return response()->json(['success' => false, 'message' => 'This key is not valid or was revoked.', 'errors' => (object) [], 'code' => 'invalid_key', 'data' => null], 401);
        }

        $hash = md5($key);
        $id = substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-4'.substr($hash, 13, 3).'-a'.substr($hash, 17, 3).'-'.substr($hash, 20, 12);

        return response()->json(['success' => true, 'message' => null, 'data' => $data($id)], $status);
    }

    /** @return array<string, mixed> */
    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(base_path("tests/Fixtures/pos-contract/{$name}.json")), true);
    }
}
