<?php

namespace Tests\Support;

use App\Services\Menu\MenuToken;

/**
 * Every request carries the token a menu page hands its scripts, as the
 * cart and the tracking send it (VerifyMenuToken). The token is dated, so a
 * test that moves the clock takes a fresh one (freshMenuToken). A test of
 * the check itself takes it off with withoutHeader(MenuToken::HEADER).
 */
trait SendsMenuToken
{
    protected function setUpSendsMenuToken(): void
    {
        $this->freshMenuToken();
    }

    /** A token as a menu page opened now would have. */
    protected function freshMenuToken(): void
    {
        $this->withHeader(MenuToken::HEADER, MenuToken::issue());
    }
}
