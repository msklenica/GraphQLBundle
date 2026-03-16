<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Listener;

use Youshido\GraphQLBundle\Event\ResolveEvent;

class SampleGraphQLEventListener
{
    public function onSampleEvent(ResolveEvent $event): void
    {
        // Add your event handling logic here
    }
}
