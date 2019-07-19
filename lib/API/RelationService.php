<?php

declare(strict_types=1);

namespace Netgen\EzPlatformSiteApi\API;

use Netgen\EzPlatformSiteApi\API\Values\Content;

/**
 * Relation service provides methods for loading relations.
 */
interface RelationService
{
    /**
     * Load single related Content from $fieldDefinitionIdentifier field in Content with given
     * $contentId, optionally limited by a list of $contentTypeIdentifiers.
     *
     * @param string|int $contentId
     * @param string $fieldDefinitionIdentifier
     * @param array $contentTypeIdentifiers
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content|null
     */
    public function loadFieldRelation(
        $contentId,
        string $fieldDefinitionIdentifier,
        array $contentTypeIdentifiers = []
    ): ?Content;

    /**
     * Load all related Content from $fieldDefinitionIdentifier field in Content with given
     * $contentId, optionally limited by a list of $contentTypeIdentifiers and $limit.
     *
     * @param string|int $contentId
     * @param string $fieldDefinitionIdentifier
     * @param array $contentTypeIdentifiers
     * @param int $limit
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content[]
     */
    public function loadFieldRelations(
        $contentId,
        string $fieldDefinitionIdentifier,
        array $contentTypeIdentifiers = [],
        int $limit = 0
    ): array;
}
