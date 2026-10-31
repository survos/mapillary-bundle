<?php

declare(strict_types=1);

namespace Survos\MapillaryBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\Kit\SurvosKitBundle;
use Survos\Mapillary\MapillaryClientInterface;
use Survos\MapillaryBundle\SurvosMapillaryBundle;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\Kernel;

final class BundleTest extends TestCase
{
    public function testContainerCompilesAndDiscoversMethodCommand(): void
    {
        $kernel = new MapillaryTestKernel('test', true);
        try {
            $kernel->boot();
            self::assertInstanceOf(MapillaryClientInterface::class, $kernel->getContainer()->get('mapillary.test_client'));
            self::assertSame('29262860710015216', $kernel->getContainer()->getParameter('survos_mapillary.client_id'));
            $application = new Application($kernel);
            $command = new CommandTester($application->find('mapillary:images:search'));
            self::assertSame(0, $command->execute(['bbox' => '19.035,47.490,19.065,47.510', '--limit' => 2]));
            self::assertStringContainsString('Returned 1 images', $command->getDisplay());
            self::assertStringContainsString('photographer', $command->getDisplay());
            self::assertStringNotContainsString('MLY|fixture', $command->getDisplay());
            self::assertStringNotContainsString('signed-secret', $command->getDisplay());
        } finally {
            $kernel->shutdown();
        }
    }
}

final class MapillaryTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new SurvosKitBundle();
        yield new SurvosMapillaryBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/mapillary-bundle-test-'.getmypid();
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir().'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['secret' => 'fixture', 'http_client' => ['enabled' => true], 'validation' => ['enabled' => true]]);
        $container->extension('survos_mapillary', ['access_token' => 'MLY|fixture', 'client_id' => '29262860710015216', 'http_client' => 'mapillary.mock_http', 'max_retries' => 0]);
        $container->services()->set('mapillary.mock_http', MockHttpClient::class)->factory([self::class, 'mockHttp']);
        $container->services()->alias('mapillary.test_client', MapillaryClientInterface::class)->public();
    }

    public static function mockHttp(): MockHttpClient
    {
        return new MockHttpClient(new MockResponse(json_encode(['data' => [[
            'id' => '123', 'captured_at' => 1700000000123, 'is_pano' => true,
            'creator' => ['id' => '99', 'username' => 'photographer'],
            'thumb_original_url' => 'https://cdn.example/signed-secret',
        ]]], JSON_THROW_ON_ERROR)));
    }
}
