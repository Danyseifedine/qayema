<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\DescribeCategoryRequest;
use App\Http\Requests\DescribeRestaurantRequest;
use App\Http\Requests\TranslateMenuTextRequest;
use App\Http\Requests\TranslateMenuTextsRequest;
use App\Models\Restaurant;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpSetup;
use App\Services\Ai\WritingHelpUses;
use Illuminate\Http\JsonResponse;

/**
 * The dashboard's AI buttons (App\Services\Ai\WritingHelp). Each answers
 * with text for the owner to check in the form; nothing is saved here.
 * Off (404) without a provider or on a package with 0 uses a day; the uses
 * a day are counted by PaceWritingHelp, and each answer says what is left.
 */
class WritingHelpController extends Controller
{
    use ResolvesRestaurant;

    public function __construct(
        private readonly WritingHelp $help,
        private readonly WritingHelpUses $uses,
    ) {}

    public function translate(TranslateMenuTextRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_unless(WritingHelpSetup::isOnFor($restaurant), 404);

        $text = $this->help->translate(
            $request->validated('text'),
            $request->validated('from'),
            $request->validated('to'),
            $request->validated('max'),
            $request->validated('fit'),
        );

        return $this->answer($restaurant, $text);
    }

    /**
     * Every piece a dish's save leaves in one language, translated in one
     * go: one use of the day, however many pieces.
     */
    public function translateMany(TranslateMenuTextsRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_unless(WritingHelpSetup::isOnFor($restaurant), 404);

        /** @var list<array{text: string, from: string, to: string, max?: int|null}> $items */
        $items = $request->validated('items');
        $texts = $this->help->translateMany($items);

        return response()->json(['data' => ['texts' => $texts, 'left' => $this->uses->left($restaurant)]]);
    }

    public function categoryDescription(DescribeCategoryRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_unless(WritingHelpSetup::isOnFor($restaurant), 404);

        $text = $this->help->describeCategory(
            $request->validated('name'),
            $request->validated('text'),
            $request->validated('language'),
        );

        return $this->answer($restaurant, $text);
    }

    public function restaurantDescription(DescribeRestaurantRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_unless(WritingHelpSetup::isOnFor($restaurant), 404);

        $text = $this->help->describeRestaurant(
            $restaurant,
            $request->validated('text'),
            $request->validated('language'),
        );

        return $this->answer($restaurant, $text);
    }

    /** The text, and the uses left today now this one is spent (null for no cap). */
    private function answer(Restaurant $restaurant, string $text): JsonResponse
    {
        return response()->json(['data' => ['text' => $text, 'left' => $this->uses->left($restaurant)]]);
    }
}
