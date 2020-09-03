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

    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    /**
     * @param string $property
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException
     *
     * @return mixed
     */
    public function __get(string $property)
    {
        switch ($property) {
            case 'prioritizedLanguages':
                return $this->configResolver->getParameter('languages');
            case 'useAlwaysAvailable':
                return $this->configResolver->getParameter('ng_site_api.use_always_available_fallback');
            case 'rootLocationId':
                return $this->configResolver->getParameter('content.tree_root.location_id');
            case 'failOnMissingField':
                return $this->configResolver->getParameter('ng_site_api.fail_on_missing_field');
        }

        throw new PropertyNotFoundException($property, \get_class($this));
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\PropertyReadOnlyException
     */
    public function __set(string $property, $value): void
    {
        throw new PropertyReadOnlyException($property, \get_class($this));
    }

    /**
     * @param string $property
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException
     *
     * @return bool
     */
    public function __isset(string $property): bool
    {
        switch ($property) {
            case 'prioritizedLanguages':
            case 'useAlwaysAvailable':
            case 'rootLocationId':
            case 'failOnMissingField':
                return true;
        }

        throw new PropertyNotFoundException($property, \get_class($this));
    }
}
