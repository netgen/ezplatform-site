<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle;

use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\AggregateRepositoryPass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\DefaultViewActionOverridePass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\InvalidRedirectConfigurationListenerPass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\PreviewControllerOverridePass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\RelationResolverRegistrationPass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\ViewBuilderRegistrationPass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Compiler\XmlTextFieldTypePass;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Configuration\Parser\BaseOptions;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Configuration\Parser\ContentView;
use Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Configuration\Parser\NamedQuery;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class NetgenEzPlatformSiteApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AggregateRepositoryPass());
        $container->addCompilerPass(new InvalidRedirectConfigurationListenerPass());
        $container->addCompilerPass(new PreviewControllerOverridePass());
        $container->addCompilerPass(new RelationResolverRegistrationPass());
        $container->addCompilerPass(new ViewBuilderRegistrationPass());
        $container->addCompilerPass(new XmlTextFieldTypePass());

        /** @var \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\EzPublishCoreExtension $coreExtension */
        $coreExtension = $container->getExtension('ezpublish');

        $coreExtension->addConfigParser(new BaseOptions());
        $coreExtension->addConfigParser(new ContentView());
        $coreExtension->addConfigParser(new NamedQuery());

        $coreExtension->addDefaultSettings(__DIR__ . '/Resources/config', ['ezplatform_default_settings.yml']);
    }
}
