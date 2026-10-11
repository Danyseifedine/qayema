<?php

namespace App\Ai\Agents\Concerns;

use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpSetup;

/**
 * The provider and model the writing help runs on, from the config, so
 * changing either is an .env edit. A null model falls back to the agent's
 * #[UseCheapestModel].
 */
trait UsesWritingHelpModel
{
    public function provider(): ?string
    {
        return WritingHelpSetup::provider();
    }

    public function model(): ?string
    {
        return WritingHelpSetup::model();
    }

    /** One call's wait on the provider; WritingHelp::TIME_BUDGET caps a whole description. */
    public function timeout(): int
    {
        return WritingHelp::TRY_SECONDS;
    }
}
