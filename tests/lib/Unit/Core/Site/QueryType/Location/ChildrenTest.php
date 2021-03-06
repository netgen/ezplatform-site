<?php

declare(strict_types=1);

namespace Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType\Location;

use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\DateMetadata;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ParentLocationId;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentName;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\DatePublished;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\Location\Priority;
use eZ\Publish\Core\Repository\Values\Content\Location as RepositoryLocation;
use Netgen\EzPlatformSearchExtra\API\Values\Content\Query\Criterion\Visible;
use Netgen\EzPlatformSiteApi\Core\Site\QueryType\Location\Children;
use Netgen\EzPlatformSiteApi\Core\Site\QueryType\QueryType;
use Netgen\EzPlatformSiteApi\Core\Site\Settings;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Location;
use Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType\QueryTypeBaseTest;
use Psr\Log\NullLogger;

/**
 * Location Children QueryType test case.
 *
 * @group query-type
 *
 * @internal
 */
final class ChildrenTest extends QueryTypeBaseTest
{
    public function providerForTestGetQuery(): array
    {
        $location = $this->getTestLocation();

        return [
            [
                false,
                [
                    'location' => $location,
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(true),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new Priority(Query::SORT_DESC),
                    ],
                ]),
            ],
            [
                false,
                [
                    'visible' => false,
                    'location' => $location,
                    'sort' => 'published asc',
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(false),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                false,
                [
                    'visible' => null,
                    'location' => $location,
                    'limit' => 12,
                    'offset' => 34,
                    'sort' => 'published desc',
                ],
                new LocationQuery([
                    'filter' => new ParentLocationId(42),
                    'limit' => 12,
                    'offset' => 34,
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                    ],
                ]),
            ],
            [
                true,
                [
                    'location' => $location,
                    'content_type' => 'article',
                    'sort' => [
                        'published asc',
                    ],
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new ContentTypeIdentifier('article'),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                true,
                [
                    'visible' => true,
                    'location' => $location,
                    'content_type' => 'article',
                    'field' => [],
                    'sort' => [
                        'published desc',
                        'name asc',
                    ],
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(true),
                        new ContentTypeIdentifier('article'),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                false,
                [
                    'location' => $location,
                    'content_type' => 'article',
                    'field' => [
                        'title' => 'Hello',
                    ],
                    'sort' => new DatePublished(Query::SORT_DESC),
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(true),
                        new ContentTypeIdentifier('article'),
                        new Field('title', Operator::EQ, 'Hello'),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                    ],
                ]),
            ],
            [
                false,
                [
                    'location' => $location,
                    'content_type' => 'article',
                    'field' => [
                        'title' => [
                            'eq' => 'Hello',
                        ],
                    ],
                    'sort' => [
                        'published desc',
                        new ContentName(Query::SORT_ASC),
                    ],
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(true),
                        new ContentTypeIdentifier('article'),
                        new Field('title', Operator::EQ, 'Hello'),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                false,
                [
                    'location' => $location,
                    'content_type' => 'article',
                    'field' => [
                        'title' => [
                            'eq' => 'Hello',
                            'gte' => 7,
                        ],
                    ],
                    'sort' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(true),
                        new ContentTypeIdentifier('article'),
                        new Field('title', Operator::EQ, 'Hello'),
                        new Field('title', Operator::GTE, 7),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                false,
                [
                    'location' => $location,
                    'creation_date' => '4 May 2018',
                ],
                new LocationQuery([
                    'filter' => new LogicalAnd([
                        new Visible(true),
                        new DateMetadata(
                            DateMetadata::CREATED,
                            Operator::EQ,
                            1525384800
                        ),
                        new ParentLocationId(42),
                    ]),
                    'sortClauses' => [
                        new Priority(Query::SORT_DESC),
                    ],
                ]),
            ],
        ];
    }

    public function providerForTestGetQueryWithInvalidOptions(): array
    {
        $location = $this->getTestLocation();

        return [
            [
                [
                    'location' => $location,
                    'content_type' => 1,
                ],
            ],
            [
                [
                    'location' => $location,
                    'field' => 1,
                ],
            ],
            [
                [
                    'location' => $location,
                    'creation_date' => true,
                ],
            ],
            [
                [
                    'location' => $location,
                    'limit' => 'five',
                ],
            ],
            [
                [
                    'location' => $location,
                    'offset' => 'ten',
                ],
            ],
        ];
    }

    public function providerForTestGetQueryWithInvalidCriteria(): array
    {
        $location = $this->getTestLocation();

        return [
            [
                [
                    'location' => $location,
                    'creation_date' => [
                        'like' => 5,
                    ],
                ],
            ],
        ];
    }

    public function providerForTestInvalidSortClauseThrowsException(): array
    {
        $location = $this->getTestLocation();

        return [
            [
                [
                    'location' => $location,
                    'sort' => 'just sort it',
                ],
            ],
        ];
    }

    protected function getQueryTypeName(): string
    {
        return 'SiteAPI:Location/Children';
    }

    protected function getQueryTypeUnderTest(bool $showHiddenItems = false): QueryType
    {
        return new Children(
            new Settings(
                ['eng-GB'],
                true,
                2,
                $showHiddenItems,
                true
            ),
            new NullLogger()
        );
    }

    protected function getTestLocation(): Location
    {
        return new Location(
            [
                'site' => false,
                'domainObjectMapper' => false,
                'innerVersionInfo' => false,
                'languageCode' => false,
                'innerLocation' => new RepositoryLocation([
                    'id' => 42,
                    'sortField' => RepositoryLocation::SORT_FIELD_PRIORITY,
                    'sortOrder' => RepositoryLocation::SORT_ORDER_DESC,
                ]),
            ],
            new NullLogger()
        );
    }

    protected function getSupportedParameters(): array
    {
        return [
            'content_type',
            'field',
            'is_field_empty',
            'creation_date',
            'modification_date',
            'section',
            'state',
            'visible',
            'sort',
            'limit',
            'offset',
            'main',
            'priority',
            'location',
        ];
    }
}
