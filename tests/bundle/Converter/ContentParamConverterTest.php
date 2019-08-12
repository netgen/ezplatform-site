<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Tests\Converter;

use Netgen\Bundle\EzPlatformSiteApiBundle\Converter\ContentParamConverter;
use Netgen\EzPlatformSiteApi\API\LoadService;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Symfony\Component\HttpFoundation\Request;

class ContentParamConverterTest extends AbstractParamConverterTest
{
    const PROPERTY_NAME = 'contentId';
    const CONTENT_CLASS = Content::class;

    /**
     * @var \Netgen\Bundle\EzPlatformSiteApiBundle\Converter\ContentParamConverter
     */
    protected $converter;

    protected function setUp(): void
    {
        $this->loadServiceMock = $this->createMock(LoadService::class);
        $this->converter = new ContentParamConverter($this->loadServiceMock);

    }

    public function testSupports(): void
    {
        $config = $this->createConfiguration(self::CONTENT_CLASS);
        $this->assertTrue($this->converter->supports($config));
        $config = $this->createConfiguration(__CLASS__);
        $this->assertFalse($this->converter->supports($config));
        $config = $this->createConfiguration();
        $this->assertFalse($this->converter->supports($config));
    }

    public function testApplyContent()
    {
        $id = 42;
        $valueObject = $this->createMock(Content::class);
        $this->loadServiceMock
            ->expects($this->once())
            ->method('loadContent')
            ->with($id)
            ->will($this->returnValue($valueObject));

        $request = new Request([], [], [self::PROPERTY_NAME => $id]);

        $config = $this->createConfiguration(self::CONTENT_CLASS, 'content');

        $this->converter->apply($request, $config);

        $this->assertInstanceOf(self::CONTENT_CLASS, $request->attributes->get('content'));
    }
    public function testApplyContentOptionalWithEmptyAttribute()
    {
        $request = new Request([], [], [self::PROPERTY_NAME => null]);
        $config = $this->createConfiguration(self::CONTENT_CLASS, 'content');
        $config->expects($this->once())
            ->method('isOptional')
            ->will($this->returnValue(true));
        $this->assertFalse($this->converter->apply($request, $config));
        $this->assertNull($request->attributes->get('content'));
    }
}
