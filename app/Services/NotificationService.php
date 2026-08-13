<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Contract\Messaging;

class NotificationService
{
    protected Messaging $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function sendToUser(User $user, string $title, string $body)
    {
        if (!$user->fcm_token) {
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body));

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            \Log::error('Firebase notification failed: ' . $e->getMessage());
            return false;
        }
    }
}