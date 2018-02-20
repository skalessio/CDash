<?php

namespace CDash\Messaging\Notification;

class NotificationDirector
{
    public function build(NotificationBuilderInterface $builder)
    {
        $subscriptions = $builder->getSubscriptions();
        $notifications = $builder->getNotifications();

        foreach ($subscriptions as $recipient => $subscription) {
            $notification = $builder->createNotification($subscription);
            $notifications->add($notification);
        }
        return $notifications;
    }
}