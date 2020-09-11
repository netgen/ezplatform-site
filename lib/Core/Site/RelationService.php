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

/**
 * @final
 *
 * @internal
 *
 * Hint against API interface instead of this service:
 *
 * @see \Netgen\EzPlatformSiteApi\API\RelationService
 */
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

    public function __construct(
        SiteInterface $site,
        RelationResolverRegistry $relationResolverRegistry
    ) {
        $this->site = $site;
        $this->relationResolverRegistry = $relationResolverRegistry;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function loadFieldRelation(
        $content,
        string $fieldDefinitionIdentifier,
        array $contentTypeIdentifiers = []
    ): ?Content {
        $relatedContentItems = $this->loadFieldRelations(
            $content,
            $fieldDefinitionIdentifier,
            $contentTypeIdentifiers
        );

        return $relatedContentItems[0] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function loadFieldRelations(
        $content,
        string $fieldDefinitionIdentifier,
        array $contentTypeIdentifiers = [],
        ?int $limit = null
    ): array {
        if (!$content instanceof Content) {
            @trigger_error(
                'Using loadFieldRelations() with Content ID as the first argument is deprecated since version 3.5, and will be removed in 4.0. Provide Content instance instead.',
                E_USER_DEPRECATED
            );

            $content = $this->site->getLoadService()->loadContent($content);
        }

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
     * Return an array of related Content items, optionally limited by $limit.
     *
     * @param array $relatedContentIds
     * @param array $contentTypeIdentifiers
     * @param null|int $limit
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content[]
     */
    private function getRelatedContentItems(array $relatedContentIds, array $contentTypeIdentifiers, ?int $limit = null): array
    {
        if (\count($relatedContentIds) === 0) {
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
            'limit' => \count($relatedContentIds),
        ]);

        $searchResult = $this->site->getFilterService()->filterContent($query);
        $contentItems = $this->extractContentItems($searchResult);

        if ($limit !== null) {
            return \array_slice($contentItems, 0, $limit);
        }

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
        $sortedIdList = \array_flip($relatedContentIds);

        $sorter = static function (Content $content1, Content $content2) use ($sortedIdList): int {
            return $sortedIdList[$content1->id] <=> $sortedIdList[$content2->id];
        };

        \usort($relatedContentItems, $sorter);
    }
}
