<?php

namespace Netgen\EzPlatformSiteApi\API\Values;

use eZ\Publish\API\Repository\Values\ValueObject;

/**
 * Site ContentInfo object provides meta information of the Site Content object.
 *
 * Corresponds to eZ Platform Repository ContentInfo object.
 * @see \eZ\Publish\API\Repository\Values\Content\ContentInfo
 *
 * @property-read int|string $id
 * @property-read int|string $contentTypeId
 * @property-read int|string $sectionId
 * @property-read int $currentVersionNo
 * @property-read bool $published
 * @property-read int|string $ownerId
 * @property-read \DateTime $modificationDate
 * @property-read \DateTime $publishedDate
 * @property-read bool $alwaysAvailable
 * @property-read string $remoteId
 * @property-read string $mainLanguageCode
 * @property-read int|string $mainLocationId
 * @property-read string $name
 * @property-read string $languageCode
 * @property-read string $contentTypeIdentifier
 * @property-read string $contentTypeName
 * @property-read string $contentTypeDescription
 * @property-read \eZ\Publish\API\Repository\Values\Content\ContentInfo $innerContentInfo
 * @property-read \eZ\Publish\API\Repository\Values\ContentType\ContentType $innerContentType
 * @property-read \Netgen\EzPlatformSiteApi\API\Values\Location|null $mainLocation
 */
abstract class ContentInfo extends ValueObject
{
}
