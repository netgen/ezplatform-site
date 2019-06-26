<?php

namespace Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType\Content\Relations;

use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\DateMetadata;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\FieldRelation;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\MatchNone;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentName;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\DatePublished;
use eZ\Publish\Core\Repository\Repository;
use Netgen\EzPlatformSiteApi\Core\Site\QueryType\Content\Relations\ReverseFields;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Content;
use Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType\QueryTypeBaseTest;

/**
 * ReverseFields Content Relation QueryType test case.
 *
 * @group query-type
 * @see \Netgen\EzPlatformSiteApi\Core\Site\QueryType\Content\Relations\ReverseFields
 */
class ReverseFieldsTest extends QueryTypeBaseTest
{
    protected function getQueryTypeName()
    {
        return 'SiteAPI:Content/Relations/ReverseFields';
    }

    protected function getQueryTypeUnderTest()
    {
        return new ReverseFields();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\eZ\Publish\API\Repository\Repository
     */
    protected function getRepositoryMock()
    {
        $repositoryMock = $this
            ->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repositoryMock->expects($this->any())
            ->method('getContentService')
            ->willReturn(false);

        $repositoryMock->expects($this->any())
            ->method('getUserService')
            ->willReturn(false);

        return $repositoryMock;
    }

    protected function getTestContent()
    {
        return new Content([
            'site' => false,
            'domainObjectMapper' => false,
            'repository' => $this->getRepositoryMock(),
            'id' => 42,
        ]);
    }

    protected function getSupportedParameters()
    {
        return [
            'content_type',
            'field',
            'is_field_empty',
            'publication_date',
            'section',
            'state',
            'sort',
            'limit',
            'offset',
            'content',
            'relation_field',
        ];
    }

    public function providerForTestGetQuery()
    {
        $content = $this->getTestContent();

        return [
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_a', 'relations_b'],
                    'limit' => 12,
                    'offset' => 34,
                    'sort' => 'published asc',
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new FieldRelation('relations_a', Operator::CONTAINS, [42]),
                        new FieldRelation('relations_b', Operator::CONTAINS, [42]),
                    ]),
                    'limit' => 12,
                    'offset' => 34,
                    'sortClauses' => [
                        new DatePublished(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_a'],
                    'content_type' => 'article',
                    'field' => [],
                    'sort' => [
                        'published asc',
                    ],
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentTypeIdentifier('article'),
                        new FieldRelation('relations_a', Operator::CONTAINS, [42]),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_b'],
                    'content_type' => 'article',
                    'field' => [
                        'title' => 'Hello',
                    ],
                    'sort' => [
                        'published desc',
                        'name asc',
                    ],
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentTypeIdentifier('article'),
                        new Field('title', Operator::EQ, 'Hello'),
                        new FieldRelation('relations_b', Operator::CONTAINS, [42]),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => [],
                    'content_type' => 'article',
                    'field' => [
                        'title' => [
                            'eq' => 'Hello',
                        ]
                    ],
                    'sort' => new DatePublished(Query::SORT_DESC),
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentTypeIdentifier('article'),
                        new Field('title', Operator::EQ, 'Hello'),
                        new MatchNone(),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                    ],
                ]),
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_a', 'relations_b'],
                    'content_type' => 'article',
                    'field' => [
                        'title' => [
                            'eq' => 'Hello',
                            'gte' => 7,
                        ]
                    ],
                    'sort' => [
                        'published desc',
                        new ContentName(Query::SORT_ASC),
                    ],
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentTypeIdentifier('article'),
                        new Field('title', Operator::EQ, 'Hello'),
                        new Field('title', Operator::GTE, 7),
                        new FieldRelation('relations_a', Operator::CONTAINS, [42]),
                        new FieldRelation('relations_b', Operator::CONTAINS, [42]),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_a', 'relations_b'],
                    'publication_date' => '4 May 2018',
                    'sort' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new DateMetadata(
                            DateMetadata::CREATED,
                            Operator::EQ,
                            1525384800
                        ),
                        new FieldRelation('relations_a', Operator::CONTAINS, [42]),
                        new FieldRelation('relations_b', Operator::CONTAINS, [42]),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
        ];
    }

    public function providerForTestGetQueryWithInvalidOptions()
    {
        $content = $this->getTestContent();

        return [
            [
                [
                    'content' => $content,
                    'content_type' => 1,
                ],
            ],
            [
                [
                    'content' => $content,
                    'content_type' => [1],
                ],
            ],
            [
                [
                    'content' => $content,
                    'field' => 1,
                ],
            ],
            [
                [
                    'content' => $content,
                    'publication_date' => true,
                ],
            ],
            [
                [
                    'content' => $content,
                    'publication_date' => [false],
                ],
            ],
            [
                [
                    'content' => $content,
                    'limit' => 'five',
                ],
            ],
            [
                [
                    'content' => $content,
                    'offset' => 'ten',
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => [1],
                ],
            ],
        ];
    }

    public function providerForTestGetQueryWithInvalidCriteria()
    {
        $content = $this->getTestContent();

        return [
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_a', 'relations_b'],
                    'publication_date' => [
                        'like' => 5,
                    ],
                ],
            ]
        ];
    }

    public function providerForTestInvalidSortClauseThrowsException()
    {
        $content = $this->getTestContent();

        return [
            [
                [
                    'content' => $content,
                    'relation_field' => ['relations_a', 'relations_b'],
                    'sort' => 'just sort it',
                ],
            ],
        ];
    }
}
