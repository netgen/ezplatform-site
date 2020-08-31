<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\DependencyInjection\Configuration\Parser;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\AbstractParser;
use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class NamedObject extends AbstractParser
{
    public const NODE_KEY = 'ng_named_objects';

    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $keyValidator = static function ($v): bool {
            foreach (\array_keys($v) as $key) {
                if (!\is_string($key) || !\preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/A', $key)) {
                    return true;
                }
            }

            return false;
        };

        $idMapper = static function ($v) {
            if (\is_int($v)) {
                return ['id' => $v];
            }

            return ['remote_id' => $v];
        };

        $nodeBuilder
            ->arrayNode(static::NODE_KEY)
            ->info('Named objects')
            ->children()
                ->arrayNode('content_items')
                    ->info('Content items by name')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->info('Content ID or remote ID')
                        ->beforeNormalization()
                            ->ifTrue(static function ($v) {return !\is_array($v);})
                            ->then($idMapper)
                        ->end()
                        ->children()
                            ->integerNode('id')
                                ->info('Content ID')
                            ->end()
                            ->scalarNode('remote_id')
                                ->info('Content remote ID')
                                ->validate()
                                    ->ifTrue(static function ($v) {return !\is_string($v);})
                                    ->thenInvalid('Content remote ID value must be of string type.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue($keyValidator)
                        ->thenInvalid('Content name must be a string conforming to a valid Twig variable name.')
                    ->end()
                ->end()
                ->arrayNode('locations')
                    ->info('Locations items by name')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->info('Location ID or remote ID')
                        ->beforeNormalization()
                            ->ifTrue(static function ($v) {return !\is_array($v);})
                            ->then($idMapper)
                        ->end()
                        ->children()
                            ->integerNode('id')
                                ->info('Location ID')
                            ->end()
                            ->scalarNode('remote_id')
                                ->info('Location remote ID')
                                ->validate()
                                    ->ifTrue(static function ($v) {return !\is_string($v);})
                                    ->thenInvalid('Location remote ID value must be of string type.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue($keyValidator)
                        ->thenInvalid('Location name must be a string conforming to a valid Twig variable name.')
                    ->end()
                ->end()
                ->arrayNode('tags')
                    ->info('Tags by name')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->info('Tag ID or remote ID')
                        ->beforeNormalization()
                            ->ifTrue(static function ($v) {return !\is_array($v);})
                            ->then($idMapper)
                        ->end()
                        ->children()
                            ->integerNode('id')
                                ->info('Tag ID')
                            ->end()
                            ->scalarNode('remote_id')
                                ->info('Tag remote ID')
                                ->validate()
                                    ->ifTrue(static function ($v) {return !\is_string($v);})
                                    ->thenInvalid('Tag remote ID value must be of string type.')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue($keyValidator)
                        ->thenInvalid('Tag name must be a string conforming to a valid Twig variable name.')
                    ->end()
                ->end()
            ->end();
    }

    public function preMap(array $config, ContextualizerInterface $contextualizer): void
    {
        $contextualizer->mapConfigArray(static::NODE_KEY, $config, ContextualizerInterface::MERGE_FROM_SECOND_LEVEL);
    }

    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer): void
    {
        // does nothing
    }
}
