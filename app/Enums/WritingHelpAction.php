<?php

namespace App\Enums;

use App\Services\Ai\WritingHelpCredits;

/**
 * What an owner asks the AI for, each at its own price in credits
 * (WritingHelpCredits): putting text into the other language, or writing a
 * description, which costs several times more.
 */
enum WritingHelpAction: string
{
    case Translation = 'translation';
    case Description = 'description';

    /**
     * The action behind a writing help route. A route not named here is
     * priced as the dearer one until it is: never free.
     */
    public static function fromRoute(?string $route): self
    {
        return match ($route) {
            'api.writing.translate', 'api.writing.translate-many' => self::Translation,
            default => self::Description,
        };
    }

    /** What one costs in credits, as the admin set it. */
    public function credits(): int
    {
        return WritingHelpCredits::priceOf($this);
    }

    /** What one costs until the admin says otherwise. */
    public function defaultCredits(): int
    {
        return match ($this) {
            self::Translation => 1,
            self::Description => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Translation => 'A translation',
            self::Description => 'A description written or improved',
        };
    }
}
