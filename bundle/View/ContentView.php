<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\View;

use eZ\Publish\API\Repository\Values\Content\Content as RepoContent;
use eZ\Publish\API\Repository\Values\Content\Location as RepoLocation;
use eZ\Publish\Core\MVC\Symfony\View\BaseView;
use eZ\Publish\Core\MVC\Symfony\View\CachableView;
use eZ\Publish\Core\MVC\Symfony\View\EmbedView;
use eZ\Publish\Core\MVC\Symfony\View\View;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use RuntimeException;

/**
 * Builds ContentView objects.
 */
class ContentView extends BaseView implements View, ContentValueView, LocationValueView, EmbedView, CachableView
{
    /**
     * Name of the QueryDefinitionCollection variable injected to the template.
     *
     * @see \Netgen\Bundle\EzPlatformSiteApiBundle\QueryType\QueryDefinitionCollection
     */
    const QUERY_DEFINITION_COLLECTION_NAME = 'ng_query_definition_collection';

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    private $content;

    /**
     * @var \Netgen\EzPlatformSiteApi\API\Values\Location|null
     */
    private $location;

    /**
     * @var bool
     */
    private $isEmbed = false;

    public function setSiteContent(Content $content): void
    {
        $this->content = $content;
    }

    public function getContent(): ?RepoContent
    {
        if (!$this->content instanceof Content) {
            return null;
        }

        return $this->content->innerContent;
    }

    public function setSiteLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getLocation(): ?RepoLocation
    {
        if (!$this->location instanceof Location) {
            return null;
        }

        return $this->location->innerLocation;
    }

    public function getSiteContent(): ?Content
    {
        return $this->content;
    }

    public function getSiteLocation(): ?Location
    {
        return $this->location;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     */
    public function setContent(RepoContent $content): void
    {
        throw new RuntimeException(
            'setContent method cannot be used with Site API content view. Use setSiteContent method instead.'
        );
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     */
    public function setLocation(RepoLocation $location): void
    {
        throw new RuntimeException(
            'setLocation method cannot be used with Site API content view. Use setSiteLocation method instead.'
        );
    }

    /**
     * Sets the value as embed / not embed.
     *
     * @param bool $value
     */
    public function setIsEmbed($value): void
    {
        $this->isEmbed = (bool)$value;
    }

    /**
     * Is the view an embed or not.
     * @return bool True if the view is an embed, false if it is not.
     */
    public function isEmbed(): bool
    {
        return $this->isEmbed;
    }

    protected function getInternalParameters(): array
    {
        $parameters = ['content' => $this->content];
        if ($this->location !== null) {
            $parameters['location'] = $this->location;
        }

        return $parameters;
    }
}
