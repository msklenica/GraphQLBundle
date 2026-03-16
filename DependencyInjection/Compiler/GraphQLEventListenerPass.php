<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GraphQLEventListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('graphql.event_dispatcher')) {
            return;
        }
        $dispatcher = $container->findDefinition('graphql.event_dispatcher');
        foreach ($container->findTaggedServiceIds('graphql.event_listener') as $id => $tags) {
            foreach ($tags as $attributes) {
                $event = $attributes['event'] ?? null;
                $method = $attributes['method'] ?? '__invoke';
                if ($event) {
                    $dispatcher->addMethodCall('addListener', [
                        $event,
                        [new Reference($id), $method]
                    ]);
                }
            }
        }
        foreach ($container->findTaggedServiceIds('graphql.event_subscriber') as $id => $tags) {
            $dispatcher->addMethodCall('addSubscriber', [new Reference($id)]);
        }
    }
}
