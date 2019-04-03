<?php


namespace Ingenerator\KohanaDoctrine\Dependency;


use Doctrine\Common\EventManager;
use Doctrine\Common\EventSubscriber;

class EventDispatchFactory
{
    /**
     * @param EventSubscriber|NULL $subscriber,...
     *
     * @return EventManager
     */
    public static function buildEventManagerWithSubscribers(EventSubscriber $subscriber = NULL)
    {
        $manager = new EventManager;
        foreach (\func_get_args() as $subscriber) {
            $manager->addEventSubscriber($subscriber);
        }

        return $manager;
    }

}