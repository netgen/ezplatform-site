<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Configuration\Parser;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\AbstractParser;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class BaseOptions extends AbstractParser
{
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('ng_fallback_to_secondary_content_view')
                ->info('Controls fallback content view rendering between Site API and eZ Platform')
            ->end();

        $nodeBuilder
            ->booleanNode('ng_fallback_without_subrequest')
                ->info('Controls whether secondary content view fallback should use a subrequest')
            ->end();

        $nodeBuilder
            ->booleanNode('ng_richtext_embed_without_subrequest')
            ->info('Controls whether RichText and XmlText embed rendering should use a subrequest')
            ->end();

        $nodeBuilder
            ->booleanNode('ng_xmltext_embed_without_subrequest')
            ->info('Controls whether RichText and XmlText embed rendering should use a subrequest')
            ->end();

        $nodeBuilder
            ->booleanNode('ng_cross_siteaccess_routing')
            ->info('Controls whether cross-siteaccess router will be used')
            ->end();

        $nodeBuilder
            ->booleanNode('ng_cross_siteaccess_routing_prefer_translation_siteaccess')
            ->info('Controls whether translation siteaccesses will be preferred for generating links')
            ->end();

        /* @noinspection NullPointerExceptionInspection */
        $nodeBuilder
            ->arrayNode('ng_cross_siteaccess_routing_external_subtree_roots')
                ->info('A list of allowed subtree root Location IDs external to the subtree root of the current siteaccess')
                ->defaultValue([])
                ->children()
                    ->integerNode('id')
                        ->info('Location ID')
                    ->end()
                ->end()
            ->end();
    }

    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer): void
    {
        $this->contextualize('ng_fallback_to_secondary_content_view', $scopeSettings, $currentScope, $contextualizer);
        $this->contextualize('ng_fallback_without_subrequest', $scopeSettings, $currentScope, $contextualizer);
        $this->contextualize('ng_richtext_embed_without_subrequest', $scopeSettings, $currentScope, $contextualizer);
        $this->contextualize('ng_xmltext_embed_without_subrequest', $scopeSettings, $currentScope, $contextualizer);
        $this->contextualize('ng_cross_siteaccess_routing', $scopeSettings, $currentScope, $contextualizer);
        $this->contextualize('ng_cross_siteaccess_routing_prefer_translation_siteaccess', $scopeSettings, $currentScope, $contextualizer);
        $this->contextualize('ng_cross_siteaccess_routing_external_subtree_roots', $scopeSettings, $currentScope, $contextualizer);
    }

    private function contextualize(
        string $parameterName,
        array &$scopeSettings,
        $currentScope,
        ContextualizerInterface $contextualizer
    ): void {
        if (\array_key_exists($parameterName, $scopeSettings)) {
            $contextualizer->setContextualParameter($parameterName, $currentScope, $scopeSettings[$parameterName]);
        }
    }
}
