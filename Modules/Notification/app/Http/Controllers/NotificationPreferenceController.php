<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notification\Models\NotificationTemplate;
use Modules\Notification\Models\UserNotificationPreference;

class NotificationPreferenceController extends Controller
{
    /**
     * Show the notification preferences form.
     */
    public function edit(Request $request): View
    {
        $templates = NotificationTemplate::where('is_active', true)->orderBy('name')->get();

        $preferences = UserNotificationPreference::where('user_id', $request->user()->id)
            ->get()
            ->keyBy('template_code');

        return view('notification::preferences', [
            'templates'   => $templates,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences'                 => ['required', 'array'],
            'preferences.*.template_code' => ['required', 'string', 'exists:notification_templates,code'],
            'preferences.*.channels'      => ['required', 'array'],
            'preferences.*.channels.*'    => ['string', 'in:mail,telegram,push,in_app'],
            'preferences.*.is_enabled'    => ['required', 'boolean'],
        ]);

        foreach ($validated['preferences'] as $pref) {
            UserNotificationPreference::updateOrCreate(
                [
                    'user_id'       => $request->user()->id,
                    'template_code' => $pref['template_code'],
                ],
                [
                    'channels'   => $pref['channels'],
                    'is_enabled' => $pref['is_enabled'],
                ]
            );
        }

        return redirect()->route('notification.preferences')
            ->with('success', 'Notification preferences updated successfully.');
    }
}
