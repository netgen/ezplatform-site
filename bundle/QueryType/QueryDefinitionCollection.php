<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\QueryType;

use OutOfBoundsException;

/**
 * QueryDefinitionCollection contains a map of QueryDefinitions by their name string.
 *
 * @see \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinition
 *
 * @internal Do not depend on this service, it can be changed without warning.
 */
final class QueryDefinitionCollection
{
    /**
     * Internal map of QueryDefinitions.
     *
     * @var \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinition[]
     */
    private $queryDefinitionMap = [];

    /**
     * Add $queryDefinition by $name to the internal map.
     *
     * @param string $name
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinition $queryDefinition
     *
     * @return void
     */
    public function add($name, QueryDefinition $queryDefinition): void
    {
        $this->queryDefinitionMap[$name] = $queryDefinition;
    }

    /**
     * Return QueryDefinition by given $name.
     *
     * @throws \OutOfBoundsException If no QueryDefinition with given $name is found.
     *
     * @param $name
     *
     * @return \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinition
     */
    public function get($name): QueryDefinition
    {
        if (array_key_exists($name, $this->queryDefinitionMap)) {
            return $this->queryDefinitionMap[$name];
        }

        throw new OutOfBoundsException(
            "Could not find QueryDefinition with name '{$name}'"
        );
    }
}
