<?php

declare(strict_types=1);

namespace Youshido\GraphQLBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GraphQLExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config  = $this->processConfiguration($configuration, $configs);

        $preparedHeaders = [];
        $headers         = !empty($config['response']['headers']) ? $config['response']['headers'] : $this->getDefaultHeaders();
        foreach ($headers as $header) {
            $preparedHeaders[$header['name']] = $header['value'];
        }

        $container->setParameter('graphql.response.headers', $preparedHeaders);
        $container->setParameter('graphql.schema_class', $config['schema_class']);
        $container->setParameter('graphql.schema_service', $config['schema_service']);
        $container->setParameter('graphql.logger', $config['logger']);
        $container->setParameter('graphql.max_complexity', $config['max_complexity']);
        $container->setParameter('graphql.response.json_pretty', $config['response']['json_pretty']);

        $container->setParameter('graphql.security.guard_config', [
            'field'     => $config['security']['guard']['field'],
            'operation' => $config['security']['guard']['operation']
        ]);

        $container->setParameter('graphql.security.black_list', $config['security']['black_list']);
        $container->setParameter('graphql.security.white_list', $config['security']['white_list']);


        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    private function getDefaultHeaders(): array
    {
        return [
            ['name' => 'Access-Control-Allow-Origin', 'value' => '*'],
            ['name' => 'Access-Control-Allow-Headers', 'value' => 'Content-Type,Authorization'],
            ['name' => 'Access-Control-Allow-Methods', 'value' => 'GET,POST,OPTIONS'],
            ['name' => 'Access-Control-Max-Age', 'value' => '3600'],
            ['name' => 'Content-Type', 'value' => 'application/json'],
        ];
    }

    public function getAlias(): string
    {
        return "graphql";
    }
}
