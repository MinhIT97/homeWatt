<?php

namespace Modules\Notification\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Models\NotificationModel;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Models\PushSubscription;
use Modules\Notification\Models\UserNotificationPreference;

class NotificationService
{
    /**
     * Send a notification to a user using the specified template.
     */
    public function send(string $templateCode, User $user, array $data = [], ?int $homeId = null): void
    {
        $template = NotificationTemplate::where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return;
        }

        $pref = UserNotificationPreference::where('user_id', $user->id)
            ->where('template_code', $templateCode)
            ->first();

        if ($pref && ! $pref->is_enabled) {
            return;
        }

        $channels = $pref?->channels ?? $template->channels;

        foreach ($channels as $channel) {
            $this->dispatchToChannel($channel, $template, $user, $data, $homeId);
        }
    }

    /**
     * Dispatch notification to the appropriate channel handler.
     */
    protected function dispatchToChannel(
        string $channel,
        NotificationTemplate $template,
        User $user,
        array $data,
        ?int $homeId
    ): void {
        $rendered = $this->renderTemplate($template, $channel, $data);

        match ($channel) {
            'telegram' => $this->sendTelegram($user, $rendered['body'] ?? ''),
            'in_app'   => $this->createInApp($user, $template, $rendered, $homeId, $data),
            'push'     => $this->sendPush($user, $rendered),
            'mail'     => $this->sendMail($user, $rendered),
            default    => null,
        };
    }

    /**
     * Render the template body and title by replacing placeholders with actual data.
     */
    protected function renderTemplate(NotificationTemplate $template, string $channel, array $data): array
    {
        $body = match ($channel) {
            'telegram' => $template->telegram_body,
            'push'     => $template->push_body,
            'mail'     => $template->mail_body,
            'in_app'   => $template->push_body,
            default    => $template->push_body,
        };

        $title = match ($channel) {
            'push' => $template->push_title,
            'mail' => $template->mail_subject,
            default => $template->name,
        };

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $body = str_replace('{{' . $key . '}}', (string) $value, $body ?? '');
                $title = str_replace('{{' . $key . '}}', (string) $value, $title ?? '');
            }
        }

        return ['title' => $title, 'body' => $body];
    }

    /**
     * Create an in-app notification record.
     */
    protected function createInApp(
        User $user,
        NotificationTemplate $template,
        array $rendered,
        ?int $homeId,
        array $data
    ): void {
        NotificationModel::create([
            'user_id'       => $user->id,
            'home_id'       => $homeId,
            'template_code' => $template->code,
            'channel'       => 'in_app',
            'title'         => $rendered['title'],
            'body'          => $rendered['body'],
            'data'          => $data,
            'sent_at'       => now(),
        ]);
    }

    /**
     * Send a Telegram message to the user.
     */
    protected function sendTelegram(User $user, string $body): void
    {
        if (empty($user->telegram_chat_id) || empty($body)) {
            return;
        }

        $token = config('services.telegram.bot_token');

        if (empty($token)) {
            Log::warning('Telegram bot token not configured.');

            return;
        }

        Http::timeout(5)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $user->telegram_chat_id,
                'text'       => $body,
                'parse_mode' => 'Markdown',
            ]);
    }

    /**
     * Send an email notification to the user.
     */
    protected function sendMail(User $user, array $rendered): void
    {
        if (empty($rendered['title']) || empty($rendered['body'])) {
            return;
        }

        Mail::raw($rendered['body'], function ($message) use ($user, $rendered) {
            $message->to($user->email)->subject($rendered['title']);
        });
    }

    /**
     * Send a push notification to all of the user's push subscriptions.
     */
    protected function sendPush(User $user, array $rendered): void
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        Log::info('Push notification queued', [
            'user_id'            => $user->id,
            'title'              => $rendered['title'],
            'subscription_count' => $subscriptions->count(),
        ]);

        // TODO: Implement actual Web Push using minishlink/web-push or similar library.
        // For each subscription, encrypt and send the push payload via the endpoint.
    }
}
