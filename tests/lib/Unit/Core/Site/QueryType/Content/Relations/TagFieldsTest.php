<?php

namespace Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType\Content\Relations;

use eZ\Publish\API\Repository\Values\Content\Field as RepoField;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentId;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\DateMetadata;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Field;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalNot;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\MatchNone;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\ContentName;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause\DatePublished;
use eZ\Publish\Core\FieldType\TextLine\Value;
use eZ\Publish\Core\Repository\Values\ContentType\FieldDefinition;
use InvalidArgumentException;
use Netgen\EzPlatformSiteApi\Core\Site\QueryType\Content\Relations\TagFields;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Content;
use Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\ContentFieldsMockTrait;
use Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType\QueryTypeBaseTest;
use Netgen\TagsBundle\API\Repository\Values\Content\Query\Criterion\TagId;
use Netgen\TagsBundle\API\Repository\Values\Tags\Tag;
use Netgen\TagsBundle\Core\FieldType\Tags\Value as TagValue;

/**
 * TagFields Content Relation QueryType test case.
 *
 * @group query-type
 * @see \Netgen\EzPlatformSiteApi\Core\Site\QueryType\Content\Relations\TagFields
 */
class TagFieldsTest extends QueryTypeBaseTest
{
    use ContentFieldsMockTrait;

    protected function getQueryTypeName()
    {
        return 'SiteAPI:Content/Relations/TagFields';
    }

    protected function getQueryTypeUnderTest()
    {
        return new TagFields();
    }

    protected function internalGetRepoFields()
    {
        return [
            new RepoField([
                'id' => 1,
                'fieldDefIdentifier' => 'tags_a',
                'value' => new TagValue([
                    new Tag([
                        'id' => 1,
                    ]),
                    new Tag([
                        'id' => 2,
                    ]),
                ]),
                'languageCode' => 'eng-GB',
                'fieldTypeIdentifier' => 'eztags',
            ]),
            new RepoField([
                'id' => 2,
                'fieldDefIdentifier' => 'tags_b',
                'value' => new TagValue([
                    new Tag([
                        'id' => 3,
                    ]),
                    new Tag([
                        'id' => 4,
                    ]),
                ]),
                'languageCode' => 'eng-GB',
                'fieldTypeIdentifier' => 'eztags',
            ]),
            new RepoField([
                'id' => 3,
                'fieldDefIdentifier' => 'not_tags',
                'value' => new Value(),
                'languageCode' => 'eng-GB',
                'fieldTypeIdentifier' => 'ezstring',
            ]),
        ];
    }

    protected function internalGetRepoFieldDefinitions()
    {
        return [
            new FieldDefinition([
                'id' => 1,
                'identifier' => 'tags_a',
                'fieldTypeIdentifier' => 'eztags',
            ]),
            new FieldDefinition([
                'id' => 2,
                'identifier' => 'tags_b',
                'fieldTypeIdentifier' => 'eztags',
            ]),
            new FieldDefinition([
                'id' => 3,
                'identifier' => 'not_tags',
                'fieldTypeIdentifier' => 'ezstring',
            ]),
        ];
    }

    protected function getTestContent()
    {
        return new Content(
            [
                'id' => 42,
                'site' => false,
                'domainObjectMapper' => $this->getDomainObjectMapper(),
                'repository' => $this->getRepositoryMock(),
                'innerContent' => $this->getRepoContent(),
                'innerVersionInfo' => $this->getRepoVersionInfo(),
            ],
            true
        );
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
            'exclude_self',
        ];
    }

    public function providerForTestGetQuery()
    {
        $content = $this->getTestContent();

        return [
            [
                [
                    'content' => $content,
                    'relation_field' => ['tags_a', 'tags_b'],
                    'limit' => 12,
                    'offset' => 34,
                    'sort' => 'published asc',
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new TagId([1, 2, 3, 4]),
                        new LogicalNot(new ContentId(42)),
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
                    'exclude_self' => true,
                    'relation_field' => ['tags_a'],
                    'content_type' => 'article',
                    'field' => [],
                    'sort' => [
                        'published asc',
                    ],
                ],
                new Query([
                    'filter' => new LogicalAnd([
                        new ContentTypeIdentifier('article'),
                        new TagId([1, 2]),
                        new LogicalNot(new ContentId(42)),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_ASC),
                    ],
                ]),
            ],
            [
                [
                    'content' => $content,
                    'exclude_self' => false,
                    'relation_field' => ['tags_b'],
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
                        new TagId([3, 4]),
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
                    'relation_field' => ['tags_a', 'tags_b'],
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
                        new TagId([1, 2, 3, 4]),
                        new LogicalNot(new ContentId(42)),
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
                    'relation_field' => ['tags_a', 'tags_b'],
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
                        new TagId([1, 2, 3, 4]),
                        new LogicalNot(New ContentId(42)),
                    ]),
                    'sortClauses' => [
                        new DatePublished(Query::SORT_DESC),
                        new ContentName(Query::SORT_ASC),
                    ],
                ]),
            ],
        ];
    }

    public function testGetQueryWithUnsupportedField()
    {
        $this->expectException(InvalidArgumentException::class);

        $queryType = $this->getQueryTypeUnderTest();
        $content = $this->getTestContent();

        $queryType->getQuery([
            'content' => $content,
            'relation_field' => ['not_tags'],
            'content_type' => 'article',
            'sort' => 'published desc',
        ]);
    }

    public function testGetQueryWithNonexistentField()
    {
        $this->expectException(InvalidArgumentException::class);

        $queryType = $this->getQueryTypeUnderTest();
        $content = $this->getTestContent();

        $queryType->getQuery([
            'content' => $content,
            'relation_field' => ['ćići'],
            'content_type' => 'article',
            'sort' => 'published desc',
        ]);
    }

    public function providerForTestGetQueryWithInvalidOptions()
    {
        $content = $this->getTestContent();

        return [
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
                    'content_type' => 1,
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
                    'content_type' => [1],
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
                    'field' => 1,
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
                    'publication_date' => true,
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
                    'publication_date' => [false],
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
                    'limit' => 'five',
                ],
            ],
            [
                [
                    'content' => $content,
                    'relation_field' => 'field',
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
                    'relation_field' => ['tags_a', 'tags_b'],
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
                    'relation_field' => ['tags_a', 'tags_b'],
                    'sort' => 'just sort it',
                ],
            ],
        ];
    }
}
