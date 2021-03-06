<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\View;

use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;

/**
 * Provides Location for the Content View when it's not explicitly given.
 */
abstract class LocationResolver
{
    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    abstract public function getLocation(Content $content): Location;
}
