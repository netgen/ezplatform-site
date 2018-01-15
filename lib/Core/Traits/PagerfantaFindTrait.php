<?php

namespace Netgen\EzPlatformSiteApi\Core\Traits;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\ContentSearchAdapter;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\ContentSearchHitAdapter;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\LocationSearchAdapter;
use Netgen\EzPlatformSiteApi\Core\Site\Pagination\Pagerfanta\LocationSearchHitAdapter;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Pagerfanta;

trait PagerfantaFindTrait
{
    use SiteAwareTrait;

    /**
     * Returns Pagerfanta pager that starts from first page
     * configured with
     * ContentSearchAdapter and FindService
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param int $currentPage
     * @param int $maxPerPage
     *
     * @return \Pagerfanta\Pagerfanta
     */
    protected function createContentSearchPager(Query $query, $currentPage, $maxPerPage)
    {
        $adapter = new ContentSearchAdapter($query, $this->getSite()->getFindService());

        return $this->getPager($adapter, $currentPage, $maxPerPage);
    }

    /**
     * Returns Pagerfanta pager that starts from first page
     * configured with
     * ContentSearchHitAdapter and FindService
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Query $query
     * @param int $currentPage
     * @param int $maxPerPage
     *
     * @return \Pagerfanta\Pagerfanta
     */
    protected function createContentSearchHitPager(Query $query, $currentPage, $maxPerPage)
    {
        $adapter = new ContentSearchHitAdapter($query, $this->getSite()->getFindService());

        return $this->getPager($adapter, $currentPage, $maxPerPage);
    }

    /**
     * Returns Pagerfanta pager that starts from first page
     * configured with
     * LocationSearchAdapter and FindService
     *
     * @param \eZ\Publish\API\Repository\Values\Content\LocationQuery $locationQuery
     * @param int $currentPage
     * @param int $maxPerPage
     *
     * @return \Pagerfanta\Pagerfanta
     */
    protected function createLocationSearchPager(LocationQuery $locationQuery, $currentPage, $maxPerPage)
    {
        $adapter = new LocationSearchAdapter($locationQuery, $this->getSite()->getFindService());

        return $this->getPager($adapter, $currentPage, $maxPerPage);
    }

    /**
     * Returns Pagerfanta pager that starts from first page
     * configured with
     * LocationSearchHitAdapter and FindService
     *
     * @param \eZ\Publish\API\Repository\Values\Content\LocationQuery $locationQuery
     * @param int $currentPage
     * @param int $maxPerPage
     *
     * @return \Pagerfanta\Pagerfanta
     */
    protected function createLocationSearchHitPager(LocationQuery $locationQuery, $currentPage, $maxPerPage)
    {
        $adapter = new LocationSearchHitAdapter($locationQuery, $this->getSite()->getFindService());

        return $this->getPager($adapter, $currentPage, $maxPerPage);
    }

    /**
     * Shorthand method for creating Pagerfanta pager
     * with preconfigured Adapter
     *
     * @param \Pagerfanta\Adapter\AdapterInterface $adapter
     * @param int $currentPage
     * @param int $maxPerPage
     *
     * @return \Pagerfanta\Pagerfanta
     */
    protected function getPager(AdapterInterface $adapter, $currentPage, $maxPerPage)
    {
        $pager = new Pagerfanta($adapter);
        $pager->setNormalizeOutOfRangePages(true);
        $pager->setMaxPerPage($maxPerPage);
        $pager->setCurrentPage($currentPage);

        return $pager;
    }
}
