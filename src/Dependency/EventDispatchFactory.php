<?php


namespace Ingenerator\KohanaDoctrine\Dependency;


use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;

class EventDispatchFactory
{

    public static function buildEventManagerWithSubscribers(EventSubscriber ...$subscribers): EventManager
    {
        $manager = new EventManager;
        foreach ($subscribers as $subscriber) {
            $manager->addEventSubscriber($subscriber);
        }

        return $manager;
    }

}
