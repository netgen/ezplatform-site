<?php

declare(strict_types=1);

namespace Netgen\EzPlatformSiteApi\Tests\Unit\Core\Site\QueryType;

use eZ\Publish\API\Repository\Values\Content\Query;
use InvalidArgumentException;
use Netgen\EzPlatformSiteApi\Core\Site\QueryType\QueryType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Base QueryType test case.
 */
abstract class QueryTypeBaseTest extends TestCase
{
    public function testGetName(): void
    {
        $queryType = $this->getQueryTypeUnderTest();

        $this->assertEquals(
            $this->getQueryTypeName(),
            $queryType::getName()
        );
    }

    public function testGetSupportedParameters(): void
    {
        $queryType = $this->getQueryTypeUnderTest();

        $this->assertEquals(
            $this->getSupportedParameters(),
            $queryType->getSupportedParameters()
        );
    }

    public function testSupportsParameterReturnsTrue(): void
    {
        $queryType = $this->getQueryTypeUnderTest();

        foreach ($this->getSupportedParameters() as $parameter) {
            $this->assertTrue($queryType->supportsParameter($parameter));
        }
    }

    public function testSupportsParameterReturnsFalse(): void
    {
        $queryType = $this->getQueryTypeUnderTest();

        $this->assertFalse($queryType->supportsParameter(\md5((string) \time())));
    }

    public function testGetBaseSupportedParameters(): void
    {
        $queryType = $this->getQueryTypeUnderTest();
        $parameters = $queryType->getSupportedParameters();

        $expectedParameters = [
            'content_type',
            'field',
            'is_field_empty',
            'creation_date',
            'section',
            'state',
            'sort',
            'limit',
            'offset',
        ];

        $this->assertGreaterThanOrEqual(\count($expectedParameters), \count($parameters));
        $parameterSet = \array_flip($parameters);

        foreach ($expectedParameters as $expectedParameter) {
            $this->assertTrue(\array_key_exists($expectedParameter, $parameterSet));
            $this->assertTrue($queryType->supportsParameter($expectedParameter));
        }
    }

    abstract public function providerForTestGetQuery();

    /**
     * @dataProvider providerForTestGetQuery
     */
    public function testGetQuery(bool $showHiddenItems, array $parameters, Query $expectedQuery): void
    {
        $queryType = $this->getQueryTypeUnderTest($showHiddenItems);

        $query = $queryType->getQuery($parameters);

        $this->assertEquals(
            $expectedQuery,
            $query
        );
    }

    abstract public function providerForTestGetQueryWithInvalidOptions();

    /**
     * @dataProvider providerForTestGetQueryWithInvalidOptions
     */
    public function testGetQueryWithInvalidOptions(array $parameters): void
    {
        $this->expectException(InvalidOptionsException::class);

        $queryType = $this->getQueryTypeUnderTest();

        $queryType->getQuery($parameters);
    }

    abstract public function providerForTestGetQueryWithInvalidCriteria();

    /**
     * @dataProvider providerForTestGetQueryWithInvalidCriteria
     */
    public function testGetQueryWithInvalidCriteria(array $parameters): void
    {
        $this->expectException(InvalidArgumentException::class);

        $queryType = $this->getQueryTypeUnderTest();

        $queryType->getQuery($parameters);
    }

    abstract public function providerForTestInvalidSortClauseThrowsException();

    /**
     * @dataProvider providerForTestInvalidSortClauseThrowsException
     */
    public function testInvalidSortClauseThrowsException(array $parameters): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->matchesRegularExpression(
            "/Sort string '.*' was not converted to a SortClause/"
        );

        $queryType = $this->getQueryTypeUnderTest();

        $queryType->getQuery($parameters);
    }

    abstract protected function getQueryTypeUnderTest(bool $showHiddenItems = false): QueryType;

    abstract protected function getQueryTypeName(): string;

    /**
     * @return string[]
     */
    abstract protected function getSupportedParameters(): array;
}
