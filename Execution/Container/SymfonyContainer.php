<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\Execution\Container;

use Symfony\Component\DependencyInjection\ContainerInterface as SymfonyContainerInterface;
use Youshido\GraphQL\Execution\Container\ContainerInterface;

/**
 * Wraps a Symfony DependencyInjection Container to implement GraphQL-php's ContainerInterface.
 *
 * This adapter class bridges the Symfony container to the GraphQL-php execution context.
 * Only the required interface methods are implemented here. Use getSymfonyContainer()
 * for access to Symfony-specific methods like setParameter(), getParameter(), etc.
 */
class SymfonyContainer implements ContainerInterface
{
    private ?SymfonyContainerInterface $container = null;

    public function __construct(
        ?SymfonyContainerInterface $container = null
    ) {
        $this->container = $container;
    }

    public function setContainer(SymfonyContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * Get a service from the container.
     *
     * @param string $id Service identifier
     * @return mixed The service instance
     * @throws \RuntimeException If container is not initialized
     */
    public function get($id)
    {
        if ($this->container === null) {
            throw new \RuntimeException('Container has not been initialized. Call setContainer() first.');
        }
        return $this->container->get($id);
    }

    /**
     * Set a service in the container.
     *
     * @param string $id Service identifier
     * @param mixed $value The service instance
     * @return self
     * @throws \RuntimeException If container is not initialized
     */
    public function set($id, $value)
    {
        if ($this->container === null) {
            throw new \RuntimeException('Container has not been initialized. Call setContainer() first.');
        }
        $this->container->set($id, $value);
        return $this;
    }

    /**
     * Remove a service from the container.
     *
     * Not supported for Symfony containers.
     *
     * @param string $id Service identifier
     * @return void
     * @throws \RuntimeException
     */
    public function remove($id)
    {
        throw new \RuntimeException('Remove method is not available for Symfony container');
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $id Service identifier
     * @return bool
     * @throws \RuntimeException If container is not initialized
     */
    public function has($id)
    {
        if ($this->container === null) {
            throw new \RuntimeException('Container has not been initialized. Call setContainer() first.');
        }
        return $this->container->has($id);
    }

    /**
     * Get the underlying Symfony container instance.
     *
     * Use this method to access Symfony-specific functionality like:
     * - setParameter()/getParameter() for container parameters
     * - initialized() to check if a service is initialized
     *
     * @return SymfonyContainerInterface The Symfony container instance
     * @throws \RuntimeException If container is not initialized
     */
    public function getSymfonyContainer(): SymfonyContainerInterface
    {
        if ($this->container === null) {
            throw new \RuntimeException('Container has not been initialized. Call setContainer() first.');
        }
        return $this->container;
    }
}
