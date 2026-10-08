<?php

namespace Tests\Support;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;

/**
 * Firebase Cloud Messaging, faked: a key is "set", every multicast is kept
 * in `$pushes`, and the tokens in `$gone` come back as unknown phones.
 */
trait FakesFirebase
{
    /** @var list<array{message: array<string, mixed>, tokens: list<string>}> */
    protected array $pushes = [];

    /**
     * @param  list<string>  $gone
     */
    protected function fakeFirebase(array $gone = []): void
    {
        config(['firebase.projects.'.config('firebase.default').'.credentials' => 'storage/app/test-key.json']);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')->andReturnUsing(function (CloudMessage $message, array $tokens) use ($gone): MulticastSendReport {
            $this->pushes[] = ['message' => $message->jsonSerialize(), 'tokens' => $tokens];

            return MulticastSendReport::withItems(array_map(
                fn (string $token): SendReport => in_array($token, $gone, true)
                    ? SendReport::failure(MessageTarget::with(MessageTarget::TOKEN, $token), NotFound::becauseTokenNotFound($token))
                    : SendReport::success(MessageTarget::with(MessageTarget::TOKEN, $token), ['name' => 'projects/qayema/messages/1']),
                $tokens,
            ));
        });

        $this->instance(Messaging::class, $messaging);
    }
}
