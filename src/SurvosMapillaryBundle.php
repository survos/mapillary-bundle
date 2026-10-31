<?php

declare(strict_types=1);

namespace Survos\MapillaryBundle;

use Survos\Kit\AbstractSurvosBundle;
use Survos\Kit\SurvosKitBundle;
use Survos\Mapillary\MapillaryClient;
use Survos\Mapillary\MapillaryClientInterface;
use Survos\Mapillary\SerializerFactory;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Serializer\Serializer;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

#[RequiredBundle(SurvosKitBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosMapillaryBundle extends AbstractSurvosBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()
            ->scalarNode('access_token')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('client_id')->defaultNull()->info('Optional public application ID; not required for public image reads.')->end()
            ->scalarNode('base_uri')->defaultValue(MapillaryClient::DEFAULT_BASE_URI)->end()
            ->scalarNode('http_client')->defaultValue('http_client')->end()
            ->integerNode('max_retries')->defaultValue(2)->min(0)->max(5)->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set('survos_mapillary.serializer', Serializer::class)->factory([SerializerFactory::class, 'create']);
        $services->set('survos_mapillary.http_client', RetryableHttpClient::class)
            ->args([service($config['http_client']), null, $config['max_retries']]);
        $services->set(MapillaryClient::class)->args([
            service('survos_mapillary.http_client'),
            service('survos_mapillary.serializer'),
            $config['access_token'],
            $config['base_uri'],
        ]);
        $services->alias(MapillaryClientInterface::class, MapillaryClient::class);
        $builder->setParameter('survos_mapillary.client_id', $config['client_id']);
    }
}
