<?php

declare(strict_types=1);

namespace Netgen\EzPlatformSiteApi\Core\Site;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Netgen\EzPlatformSiteApi\API\RelationService as RelationServiceInterface;
use Netgen\EzPlatformSiteApi\API\Site as SiteInterface;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\Core\Site\Plugins\FieldType\RelationResolver\Registry as RelationResolverRegistry;
use Netgen\EzPlatformSiteApi\Core\Traits\SearchResultExtractorTrait;

class RelationService implements RelationServiceInterface
{
    use SearchResultExtractorTrait;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    private $site;

    /**
     * @var \Netgen\EzPlatformSiteApi\Core\Site\Plugins\FieldType\RelationResolver\Registry
     */
    private $relationResolverRegistry;

    /**
     * @param \Netgen\EzPlatformSiteApi\API\Site $site
     * @param \Netgen\EzPlatformSiteApi\Core\Site\Plugins\FieldType\RelationResolver\Registry $relationResolverRegistry
     */
    public function __construct(
        SiteInterface $site,
        RelationResolverRegistry $relationResolverRegistry
    ) {
        $this->site = $site;
        $this->relationResolverRegistry = $relationResolverRegistry;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function loadFieldRelation(
        $contentId,
        string $fieldDefinitionIdentifier,
        array $contentTypeIdentifiers = []
    ): ?Content {
        $relatedContentItems = $this->loadFieldRelations(
            $contentId,
            $fieldDefinitionIdentifier,
            $contentTypeIdentifiers
        );

        return count($relatedContentItems) ? reset($relatedContentItems) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function loadFieldRelations(
        $contentId,
        string $fieldDefinitionIdentifier,
        array $contentTypeIdentifiers = [],
        $limit = 0
    ) {
        $content = $this->site->getLoadService()->loadContent($contentId);

        $field = $content->getField($fieldDefinitionIdentifier);
        $relationResolver = $this->relationResolverRegistry->get($field->fieldTypeIdentifier);

        $relatedContentIds = $relationResolver->getRelationIds($field);
        $relatedContentItems = $this->getRelatedContentItems(
            $relatedContentIds,
            $contentTypeIdentifiers,
            $limit
        );
        $this->sortByIdOrder($relatedContentItems, $relatedContentIds);

        return $relatedContentItems;
    }

    /**
     * Return an array of all related Content from the given arguments if $limit is not provided.
     *
     * @param array $relatedContentIds
     * @param array $contentTypeIdentifiers
     * @param int $limit
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Content[]
     */
    private function getRelatedContentItems(array $relatedContentIds, array $contentTypeIdentifiers, $limit = 0): array
    {
        if (count($relatedContentIds) === 0) {
            return [];
        }

        $criteria = new ContentId($relatedContentIds);

        if (!empty($contentTypeIdentifiers)) {
            $criteria = new LogicalAnd([
                $criteria,
                new ContentTypeIdentifier($contentTypeIdentifiers),
            ]);
        }

        $query = new Query([
            'filter' => $criteria,
            'limit' => $limit ? $limit : count($relatedContentIds),
        ]);

        $searchResult = $this->site->getFilterService()->filterContent($query);
        /** @var \eZ\Publish\API\Repository\Values\Content\Content[] $contentItems */
        $contentItems = $this->extractValueObjects($searchResult);

        return $contentItems;
    }

    /**
     * Sorts $relatedContentItems to match order from $relatedContentIds.
     *
     * @param array $relatedContentItems
     * @param array $relatedContentIds
     */
    private function sortByIdOrder(array &$relatedContentItems, array $relatedContentIds): void
    {
        $sortedIdList = array_flip($relatedContentIds);

        $sorter = static function (Content $content1, Content $content2) use ($sortedIdList): int {
            return $sortedIdList[$content1->id] <=> $sortedIdList[$content2->id];
        };

        usort($relatedContentItems, $sorter);
    }
}
