<?php

namespace OpenEMR\Modules\DiscoverAndChange\Assessments\Listeners;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

interface IStaticEventSubscriber
{
    /** @return void */
    static function subscribeToEvents(Container $container, EventDispatcherInterface $eventDispatcher);
}
