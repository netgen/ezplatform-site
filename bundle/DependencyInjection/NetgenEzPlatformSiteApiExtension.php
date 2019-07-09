<?php

namespace Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ConfigurationProcessor;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class NetgenEzPlatformSiteApiExtension extends Extension implements PrependExtensionInterface
{
    public function getAlias()
    {
        return 'netgen_ez_platform_site_api';
    }

    /**
     * {@inheritDoc}
     *
     * @return \Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($this->getAlias());
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $activatedBundles = array_keys($container->getParameter('kernel.bundles'));

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $coreFileLocator = new FileLocator(__DIR__ . '/../../lib/Resources/config');
        $coreLoader = new Loader\YamlFileLoader($container, $coreFileLocator);
        $coreLoader->load('services.yml');

        if (in_array('NetgenTagsBundle', $activatedBundles, true)) {
            $coreLoader->load('query_types/netgen_tags_dependant.yml');
        }

        $fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new Loader\YamlFileLoader($container, $fileLocator);
        $loader->load('services.yml');
        $loader->load('view.yml');

        $processor = new ConfigurationProcessor($container, $this->getAlias());
        $processor->mapConfig(
            $config,
            function ($scopeSettings, $currentScope, ContextualizerInterface $contextualizer) {
                foreach ($scopeSettings as $key => $value) {
                    $contextualizer->setContextualParameter($key, $currentScope, $value);
                }
            }
        );

        if (!$container->hasParameter('ezsettings.default.ngcontent_view')) {
            // Default value for ngcontent_view template rules
            // Setting this through the config file causes issues in eZ kernel 6.11+
            $container->setParameter('ezsettings.default.ngcontent_view', []);
        }
    }

    public function prepend(ContainerBuilder $container)
    {
        $configFile = __DIR__ . '/../Resources/config/ezplatform.yml';
        $config = Yaml::parse(file_get_contents($configFile));
        $container->addResource(new FileResource($configFile));

        $container->prependExtensionConfig('ezpublish', $config);
    }
}
