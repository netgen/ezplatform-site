<?php

declare(strict_types=1);

namespace Netgen\EzPlatformSiteApi\Core\Site;

use eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException;
use eZ\Publish\API\Repository\Exceptions\PropertyReadOnlyException;
use Netgen\EzPlatformSiteApi\API\Settings as BaseSettings;

final class Settings extends BaseSettings
{
    /**
     * @var string[]
     */
    private $prioritizedLanguages;

    /**
     * @var bool
     */
    private $useAlwaysAvailable;

    /**
     * @var int|string
     */
    private $rootLocationId;

    /**
     * @var bool
     */
    private $failOnMissingFields;

    /**
     * @param string[] $prioritizedLanguages
     * @param bool $useAlwaysAvailable
     * @param int|string $rootLocationId
     * @param bool $failOnMissingFields
     */
    public function __construct(
        array $prioritizedLanguages,
        bool $useAlwaysAvailable,
        $rootLocationId,
        bool $failOnMissingFields
    ) {
        $this->prioritizedLanguages = $prioritizedLanguages;
        $this->useAlwaysAvailable = $useAlwaysAvailable;
        $this->rootLocationId = $rootLocationId;
        $this->failOnMissingFields = $failOnMissingFields;
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get(string $property)
    {
        switch ($property) {
            case 'prioritizedLanguages':
                return $this->prioritizedLanguages;
            case 'useAlwaysAvailable':
                return $this->useAlwaysAvailable;
            case 'rootLocationId':
                return $this->rootLocationId;
            case 'failOnMissingFields':
                return $this->failOnMissingFields;
        }

        throw new PropertyNotFoundException($property, get_class($this));
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\PropertyReadOnlyException
     *
     * @param string $property
     * @param mixed $value
     */
    public function __set(string $property, $value): void
    {
        throw new PropertyReadOnlyException($property, get_class($this));
    }

    /**
     * @throws \eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException
     *
     * @param string $property
     *
     * @return bool
     */
    public function __isset(string $property): bool
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
