<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinitionCollection;
use Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryExecutor;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Pagerfanta\Pagerfanta;
use Twig\Error\RuntimeError;

/**
 * Twig extension runtime for executing queries from the QueryDefinitionCollection injected
 * into the template.
 */
class QueryRuntime
{
    /**
     * @var \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryExecutor
     */
    private $queryExecutor;

    /**
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryExecutor $queryExecutor
     */
    public function __construct(QueryExecutor $queryExecutor)
    {
        $this->queryExecutor = $queryExecutor;
    }

    /**
     * @param $context
     * @param string $name
     *
     * @throws \Pagerfanta\Exception\Exception
     * @throws \Twig\Error\RuntimeError
     *
     * @return \Pagerfanta\Pagerfanta
     */
    public function executeQuery($context, string $name): Pagerfanta
    {
        return $this->queryExecutor->execute(
            $this->getQueryDefinitionCollection($context)->get($name),
            true
        );
    }

    /**
     * @param $context
     * @param string $name
     *
     * @throws \Pagerfanta\Exception\Exception
     * @throws \Twig\Error\RuntimeError
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    public function executeRawQuery($context, $name): SearchResult
    {
        return $this->queryExecutor->execute(
            $this->getQueryDefinitionCollection($context)->get($name),
            false
        );
    }

    /**
     * Returns the QueryDefinitionCollection variable from the given $context.
     *
     * @param mixed $context
     *
     * @throws \Twig\Error\RuntimeError
     *
     * @return \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinitionCollection
     */
    private function getQueryDefinitionCollection($context): QueryDefinitionCollection
    {
        $variableName = ContentView::QUERY_DEFINITION_COLLECTION_NAME;

        if (is_array($context) && array_key_exists($variableName, $context)) {
            return $context[$variableName];
        }

        throw new RuntimeError(
            "Could not find QueryDefinitionCollection variable '{$variableName}'"
        );
    }
}
