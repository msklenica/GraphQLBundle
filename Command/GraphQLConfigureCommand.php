<?php
declare(strict_types=1);

namespace Youshido\GraphQLBundle\Command;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GraphQLConfigureCommand extends Command
{
    public const PROJECT_NAMESPACE = 'App';

    public function __construct(protected Container $container)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('graphql:configure')
            ->setDescription('Generates GraphQL Schema class')
            ->addOption('composer');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isComposerCall = $input->getOption('composer');

        $projectDir = $this->container->getParameter('kernel.project_dir');
        $rootDir    = $projectDir . DIRECTORY_SEPARATOR . 'src';
        $configFile = $projectDir . DIRECTORY_SEPARATOR . 'config/packages/graphql.yml';

        $className       = 'Schema';
        $schemaNamespace = self::PROJECT_NAMESPACE . '\\GraphQL';
        $graphqlPath     = rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'GraphQL';
        $classPath       = $graphqlPath . DIRECTORY_SEPARATOR . $className . '.php';

        $inputHelper = $this->getHelper('question');
        if (file_exists($classPath)) {
            if (!$isComposerCall) {
                $output->writeln(sprintf('Schema class %s was found.', $schemaNamespace . '\\' . $className));
            }
        } else {
            $question = new ConfirmationQuestion(sprintf('Confirm creating class at %s ? [Y/n]', $schemaNamespace . '\\' . $className), true);
            if (!$inputHelper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            if (!is_dir($graphqlPath)) {
                if (!mkdir($graphqlPath, 0755, true) && !is_dir($graphqlPath)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $graphqlPath));
                }
            }
            file_put_contents($classPath, $this->getSchemaClassTemplate($schemaNamespace, $className));

            $output->writeln('Schema file has been created at');
            $output->writeln($classPath . "\n");

            if (!file_exists($configFile)) {
                $question = new ConfirmationQuestion(sprintf('Config file not found (look at %s). Create it? [Y/n]', $configFile), true);
                if (!$inputHelper->ask($input, $output, $question)) {
                    return Command::SUCCESS;
                }

                touch($configFile);
            }

            $originalConfigData = @file_get_contents($configFile);
            if ($originalConfigData === false) {
                throw new \RuntimeException(sprintf('Unable to read configuration file "%s"', $configFile));
            }
            if (!str_contains($originalConfigData, 'graphql')) {
                $projectNameSpace = self::PROJECT_NAMESPACE;
                $configData       = <<<CONFIG
graphql:
    schema_class: "{$projectNameSpace}\\\\GraphQL\\\\{$className}"

CONFIG;
                file_put_contents($configFile, $configData . $originalConfigData);
            }
        }
        if (!$this->graphQLRouteExists()) {
            $question = new ConfirmationQuestion('Confirm adding GraphQL route? [Y/n]', true);
            $resource = $this->getMainRouteConfig();
            if ($resource && $inputHelper->ask($input, $output, $question)) {
                $routeConfigData = <<<CONFIG

graphql:
    resource: "@GraphQLBundle/Controller/"
CONFIG;
                file_put_contents($resource, $routeConfigData, FILE_APPEND);
                $output->writeln('Config was added to ' . $resource);
            }
        } else {
            if (!$isComposerCall) {
                $output->writeln('GraphQL default route was found.');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws \Exception
     */
    protected function getMainRouteConfig(): string|null
    {
        $routerResources = $this->container->get('router')->getRouteCollection()->getResources();
        foreach ($routerResources as $resource) {
            /** @var FileResource|DirectoryResource $resource */
            if (method_exists($resource, 'getResource') && str_ends_with($resource->getResource(), 'routes.yaml')) {
                return $resource->getResource();
            }
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    protected function graphQLRouteExists(): bool
    {
        $routerResources = $this->container->get('router')->getRouteCollection()->getResources();
        foreach ($routerResources as $resource) {
            /** @var FileResource|DirectoryResource $resource */
            if (method_exists($resource, 'getResource') && str_contains($resource->getResource(), 'GraphQLController.php')) {
                return true;
            }
        }

        return false;
    }

    protected function getSchemaClassTemplate(string $nameSpace, string $className = 'Schema'): string
    {
        $tpl = <<<TEXT
<?php
/**
 * This class was automatically generated by GraphQL Schema generator
 */

namespace $nameSpace;

use Youshido\GraphQL\Schema\AbstractSchema;
use Youshido\GraphQL\Config\Schema\SchemaConfig;
use Youshido\GraphQL\Type\Scalar\StringType;

class $className extends AbstractSchema
{
    public function build(SchemaConfig \$config)
    {
        \$config->getQuery()->addFields([
            'hello' => [
                'type'    => new StringType(),
                'args'    => [
                    'name' => [
                        'type' => new StringType(),
                        'defaultValue' => 'Stranger'
                    ]
                ],
                'resolve' => function (\$context, \$args) {
                    return 'Hello ' . \$args['name'];
                }
            ]
        ]);
    }

}


TEXT;

        return $tpl;
    }
}
