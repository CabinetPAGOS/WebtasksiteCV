<?php 
// src/EventListener/WebtaskListener.php
namespace App\EventListener;

use App\Event\WebtaskEvent;
use App\Services\NotificationService;

class WebtaskListener
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function onWebtaskModified(WebtaskEvent $event)
    {
        $webtask = $event->getWebtask();

        $usersToNotify = $this->getUsersToNotify($webtask);

        foreach ($usersToNotify as $user) {
            $this->notificationService->sendNotification($user, $webtask);
        }
    }

    private function getUsersToNotify($webtask)
    {
       
        return $webtask->getAssignedUsers();
    }
}
