<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\DescribeCategoryRequest;
use App\Http\Requests\DescribeRestaurantRequest;
use App\Http\Requests\TranslateMenuTextRequest;
use App\Http\Requests\TranslateMenuTextsRequest;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpSetup;
use Illuminate\Http\JsonResponse;

/**
 * The dashboard's AI buttons (App\Services\Ai\WritingHelp). Each answers
 * with text for the owner to check in the form; nothing is saved here.
 * Off (404) without a provider or on a package with no credits; the credits
 * a month are counted by PaceWritingHelp, which adds what is left to each
 * answer.
 */
class WritingHelpController extends Controller
{
    use ResolvesRestaurant;

    public function __construct(private readonly WritingHelp $help) {}

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

        return $this->answer($text);
    }

    /**
     * Every piece a dish's save leaves in one language, translated in one
     * go: one action's credits, however many pieces.
     */
    public function translateMany(TranslateMenuTextsRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_unless(WritingHelpSetup::isOnFor($restaurant), 404);

        /** @var list<array{text: string, from: string, to: string, max?: int|null}> $items */
        $items = $request->validated('items');
        $texts = $this->help->translateMany($items);

        return response()->json(['data' => ['texts' => $texts]]);
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

        return $this->answer($text);
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

        return $this->answer($text);
    }

    private function answer(string $text): JsonResponse
    {
        return response()->json(['data' => ['text' => $text]]);
    }
}
