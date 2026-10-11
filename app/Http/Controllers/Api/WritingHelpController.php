<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\DescribeCategoryRequest;
use App\Http\Requests\DescribeRestaurantRequest;
use App\Http\Requests\TranslateMenuTextRequest;
use App\Services\Ai\WritingHelp;
use Illuminate\Http\JsonResponse;

/**
 * The dashboard's AI buttons (App\Services\Ai\WritingHelp). Each answers
 * with text for the owner to check in the form; nothing is saved here.
 * Capped per restaurant per day (the `writing-help` limiter).
 */
class WritingHelpController extends Controller
{
    use ResolvesRestaurant;

    public function __construct(private readonly WritingHelp $help) {}

    public function translate(TranslateMenuTextRequest $request): JsonResponse
    {
        abort_unless(WritingHelp::isOn(), 404);

        $text = $this->help->translate(
            $request->validated('text'),
            $request->validated('from'),
            $request->validated('to'),
        );

        return response()->json(['data' => ['text' => $text]]);
    }

    public function categoryDescription(DescribeCategoryRequest $request): JsonResponse
    {
        abort_unless(WritingHelp::isOn(), 404);

        $text = $this->help->describeCategory(
            $request->validated('name'),
            $request->validated('text'),
            $request->validated('language'),
        );

        return response()->json(['data' => ['text' => $text]]);
    }

    public function restaurantDescription(DescribeRestaurantRequest $request): JsonResponse
    {
        abort_unless(WritingHelp::isOn(), 404);

        $text = $this->help->describeRestaurant(
            $this->restaurant($request),
            $request->validated('text'),
            $request->validated('language'),
        );

        return response()->json(['data' => ['text' => $text]]);
    }
}
