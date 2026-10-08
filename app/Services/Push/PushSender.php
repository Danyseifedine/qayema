<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * Sends a notification to phones through Firebase Cloud Messaging. Without
 * a service-account key (FIREBASE_CREDENTIALS empty: tests, e2e, a server
 * not set up) nothing is sent. A phone Firebase no longer knows (the app
 * removed, its token replaced) is forgotten. Never throws: a notification
 * that cannot go must not undo what caused it.
 */
class PushSender
{
    /** Firebase takes at most 500 tokens per multicast. */
    private const BATCH = 500;

    /** The Android notification channel the admin app creates. */
    public const ANDROID_CHANNEL = 'admin_alerts';

    public static function enabled(): bool
    {
        $project = (string) config('firebase.default');

        return filled(config("firebase.projects.{$project}.credentials"));
    }

    /**
     * @param  Builder<DeviceToken>  $devices
     * @return int The phones it reached.
     */
    public function send(Builder $devices, PushMessage $message): int
    {
        if (! self::enabled()) {
            return 0;
        }

        $reached = 0;

        $devices->select(['id', 'token'])->chunkById(self::BATCH, function ($batch) use ($message, &$reached): void {
            $reached += $this->sendBatch($batch->pluck('token')->all(), $message);
        });

        return $reached;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function sendBatch(array $tokens, PushMessage $message): int
    {
        try {
            $report = app(Messaging::class)->sendMulticast($this->cloudMessage($message), $tokens);
        } catch (\Throwable $e) {
            Log::warning('Push notification not sent.', ['title' => $message->title, 'error' => $e->getMessage()]);

            return 0;
        }

        $gone = [...$report->unknownTokens(), ...$report->invalidTokens()];
        if ($gone !== []) {
            DeviceToken::query()->whereIn('token', $gone)->delete();
        }

        return $report->successes()->count();
    }

    private function cloudMessage(PushMessage $message): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(Notification::create($message->title, $message->body))
            ->withData($message->data)
            ->withAndroidConfig(AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => self::ANDROID_CHANNEL,
                    // Drawn by the app (res/drawable/ic_stat_qayema): the Q in
                    // white, tinted gold, as Android shows status-bar icons.
                    'icon' => 'ic_stat_qayema',
                    'color' => '#F8D38D',
                ],
            ]));
    }
}
