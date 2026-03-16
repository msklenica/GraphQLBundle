<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Youshido\GraphQLBundle\DependencyInjection\Compiler\GraphQlCompilerPass;
use Youshido\GraphQLBundle\DependencyInjection\Compiler\GraphQLEventListenerPass;
use Youshido\GraphQLBundle\DependencyInjection\GraphQLExtension;

class GraphQLBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GraphQlCompilerPass());
        // RegisterListenersPass is for the main event dispatcher only in Symfony 7/8
        $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new GraphQLEventListenerPass()); // Register custom event listeners/subscribers
        // For custom event dispatchers, register listeners/subscribers via service tags in your YAML/XML config or a custom CompilerPass.
    }

    public function getContainerExtension(): GraphQLExtension
    {
        if (null === $this->extension) {
            $this->extension = new GraphQLExtension();
        }

        return $this->extension;
    }
}
