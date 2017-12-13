<?php

namespace Netgen\EzPlatformSiteApi\API\Adapter;

use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\SPI\Search\Capable;
use eZ\Publish\SPI\Search\Handler;
use Netgen\EzPlatformSiteApi\API\FilterService;

/**
 * This class is an adapter from Site API filter service to SearchService interface
 * from eZ Publish kernel. The point is being able to replace usage of eZ search service
 * with Site API filter service without touching consuming code.
 *
 * Methods implemented here do not use $languageFilter argument since it is handled automatically
 * by the filter service itself.
 *
 * As for $filterOnUserPermissions, filter service doesn't support it, so it is simply ignored.
 */
final class FilterServiceAdapter implements SearchService
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\FilterService
     */
    private $filterService;

    /**
     * @var \eZ\Publish\SPI\Search\Handler
     */
    private $searchHandler;

    public function __construct(FilterService $filterService, Handler $searchHandler)
    {
        $this->filterService = $filterService;
        $this->searchHandler = $searchHandler;
    }

    public function findContent(Query $query, array $languageFilter = [], $filterOnUserPermissions = true)
    {
        $searchResult = $this->filterService->filterContent($query);

        foreach ($searchResult->searchHits as $searchHit) {
            $searchHit->valueObject = $searchHit->valueObject->innerContent;
        }

        return $searchResult;
    }

    public function findContentInfo(Query $query, array $languageFilter = [], $filterOnUserPermissions = true)
    {
        $searchResult = $this->filterService->filterContent($query);

        foreach ($searchResult->searchHits as $searchHit) {
            $searchHit->valueObject = $searchHit->valueObject->innerContentInfo;
        }

        return $searchResult;
    }

    public function findLocations(LocationQuery $query, array $languageFilter = [], $filterOnUserPermissions = true)
    {
        $searchResult = $this->filterService->filterLocations($query);

        foreach ($searchResult->searchHits as $searchHit) {
            $searchHit->valueObject = $searchHit->valueObject->innerLocation;
        }

        return $searchResult;
    }

    public function findSingle(Criterion $filter, array $languageFilter = [], $filterOnUserPermissions = true)
    {
        $query = new Query();
        $query->filter = $filter;
        $query->limit = 1;

        $searchResult = $this->filterService->filterContent($query);

        if (!$searchResult->totalCount) {
            throw new NotFoundException('Content', 'findSingle() found no content for given $filter');
        } elseif ($searchResult->totalCount > 1) {
            throw new InvalidArgumentException('totalCount', 'findSingle() found more then one item for given $filter');
        }

        return $searchResult->searchHits[0]->valueObject->innerContent;
    }

    public function suggest($prefix, $fieldPaths = [], $limit = 10, Criterion $filter = null)
    {
    }

    public function supports($capabilityFlag)
    {
        if ($this->searchHandler instanceof Capable) {
            return $this->searchHandler->supports($capabilityFlag);
        }

        return false;
    }
}
