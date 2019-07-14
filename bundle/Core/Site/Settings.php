<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Core\Site;

use eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException;
use eZ\Publish\API\Repository\Exceptions\PropertyReadOnlyException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Netgen\EzPlatformSiteApi\API\Settings as BaseSettings;

final class Settings extends BaseSettings
{
    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @param \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver
     */
    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function __get($property)
    {
        switch ($property) {
            case 'prioritizedLanguages':
                return $this->configResolver->getParameter('languages');
            case 'useAlwaysAvailable':
                return $this->configResolver->getParameter(
                    'use_always_available_fallback',
                    'netgen_ez_platform_site_api'
                );
            case 'rootLocationId':
                return $this->configResolver->getParameter('content.tree_root.location_id');
            case 'failOnMissingFields':
                return $this->configResolver->getParameter(
                    'fail_on_missing_fields',
                    'netgen_ez_platform_site_api'
                );
        }

        throw new PropertyNotFoundException($property, get_class($this));
    }

    public function __set($property, $value): void
    {
        throw new PropertyReadOnlyException($property, get_class($this));
    }

    public function __isset($property): bool
    {
        switch ($property) {
            case 'prioritizedLanguages':
            case 'useAlwaysAvailable':
            case 'rootLocationId':
            case 'failOnMissingFields':
                return true;
        }

        throw new PropertyNotFoundException($property, get_class($this));
    }
}
