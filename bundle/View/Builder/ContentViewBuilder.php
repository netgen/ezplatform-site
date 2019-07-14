<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\View\Builder;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content as APIContent;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\Base\Exceptions\UnauthorizedException;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use eZ\Publish\Core\MVC\Symfony\View\Builder\ViewBuilder;
use eZ\Publish\Core\MVC\Symfony\View\Configurator;
use eZ\Publish\Core\MVC\Symfony\View\EmbedView;
use eZ\Publish\Core\MVC\Symfony\View\ParametersInjector;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\EzPlatformSiteApi\API\Site;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Builds ContentView objects.
 */
class ContentViewBuilder implements ViewBuilder
{
    /**
     * @var \Netgen\EzPlatformSiteApi\API\Site
     */
    private $site;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    private $repository;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\View\Configurator
     */
    private $viewConfigurator;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\View\ParametersInjector
     */
    private $viewParametersInjector;

    public function __construct(
        Site $site,
        Repository $repository,
        AuthorizationCheckerInterface $authorizationChecker,
        Configurator $viewConfigurator,
        ParametersInjector $viewParametersInjector
    ) {
        $this->site = $site;
        $this->repository = $repository;
        $this->authorizationChecker = $authorizationChecker;
        $this->viewConfigurator = $viewConfigurator;
        $this->viewParametersInjector = $viewParametersInjector;
    }

    public function matches($argument): bool
    {
        return is_string($argument) && strpos($argument, 'ng_content:') !== false;
    }

    /**
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     *         If both contentId and locationId parameters are missing
     * @throws \eZ\Publish\Core\Base\Exceptions\UnauthorizedException
     *
     * @param array $parameters
     *
     * @return \eZ\Publish\Core\MVC\Symfony\View\View
     */
    public function buildView(array $parameters): ContentView
    {
        $view = new ContentView(null, [], $parameters['viewType']);
        $view->setIsEmbed($this->isEmbed($parameters));

        if ($view->isEmbed() && $parameters['viewType'] === null) {
            $view->setViewType(EmbedView::DEFAULT_VIEW_TYPE);
        }

        if (isset($parameters['locationId'])) {
            $location = $this->loadLocation($parameters['locationId']);
        } elseif (isset($parameters['location'])) {
            $location = $parameters['location'];
            if (!$location instanceof Location && $location instanceof APILocation) {
                $location = $this->loadLocation($location->id, false);
            }
        } else {
            $location = null;
        }

        if (isset($parameters['content'])) {
            $content = $parameters['content'];
            if (!$content instanceof Content && $content instanceof APIContent) {
                $content = $this->loadContent($content->contentInfo->id);
            }
        } else {
            if (isset($parameters['contentId'])) {
                $contentId = $parameters['contentId'];
            } elseif (isset($location)) {
                $contentId = $location->contentInfo->id;
            } else {
                throw new InvalidArgumentException(
                    'Content',
                    'No content could be loaded from parameters'
                );
            }

            $content = $view->isEmbed() ?
                $this->loadEmbeddedContent($contentId, $location) :
                $this->loadContent($contentId);
        }

        $view->setSiteContent($content);
        if (isset($location)) {
            if ($location->contentInfo->id !== $content->id) {
                throw new InvalidArgumentException(
                    'Location',
                    'Provided location does not belong to selected content'
                );
            }

            $view->setSiteLocation($location);
        }

        $this->viewParametersInjector->injectViewParameters($view, $parameters);
        $this->viewConfigurator->configure($view);

        return $view;
    }

    /**
     * Loads Content with id $contentId.
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\UnauthorizedException
     *
     * @param mixed $contentId
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    private function loadContent($contentId): Content
    {
        return $this->site->getLoadService()->loadContent($contentId);
    }

    /**
     * Loads the embedded content with id $contentId.
     * Will load the content with sudo(), and check if the user can view_embed this content, for the given location
     * if provided.
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\UnauthorizedException
     *
     * @param string|int $contentId
     * @param \Netgen\EzPlatformSiteApi\API\Values\Location $location
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    private function loadEmbeddedContent($contentId, Location $location = null): Content
    {
        /** @var \eZ\Publish\API\Repository\Values\Content\ContentInfo $contentInfo */
        $contentInfo = $this->repository->sudo(
            function (Repository $repository) use ($contentId) {
                return $repository->getContentService()->loadContentInfo($contentId);
            }
        );

        if (!$this->canRead($contentInfo, $location)) {
            throw new UnauthorizedException(
                'content', 'read|view_embed',
                ['contentId' => $contentId, 'locationId' => $location !== null ? $location->id : 'n/a']
            );
        }

        // Check that Content is published, since sudo allows loading unpublished content.
        if (
            !$contentInfo->published &&
            !$this->authorizationChecker->isGranted(
                new AuthorizationAttribute('content', 'versionread', ['valueObject' => $contentInfo])
            )
        ) {
            throw new UnauthorizedException('content', 'versionread', ['contentId' => $contentId]);
        }

        return $this->site->getLoadService()->loadContent($contentId);
    }

    /**
     * Loads a visible Location.
     * @todo Do we need to handle permissions here ?
     *
     * @param string|int $locationId
     * @param bool $checkVisibility
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    private function loadLocation($locationId, $checkVisibility = true): Location
    {
        $location = $this->repository->sudo(
            function (Repository $repository) use ($locationId) {
                return $this->site->getLoadService()->loadLocation($locationId);
            }
        );

        if ($checkVisibility && $location->innerLocation->invisible) {
            throw new NotFoundHttpException(
                'Location cannot be displayed as it is flagged as invisible.'
            );
        }

        return $location;
    }

    /**
     * Checks if a user can read a content, or view it as an embed.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\ContentInfo $contentInfo
     * @param $location
     *
     * @return bool
     */
    private function canRead(ContentInfo $contentInfo, Location $location = null): bool
    {
        $limitations = ['valueObject' => $contentInfo];
        if (isset($location)) {
            $limitations['targets'] = $location->innerLocation;
        }

        $readAttribute = new AuthorizationAttribute('content', 'read', $limitations);
        $viewEmbedAttribute = new AuthorizationAttribute('content', 'view_embed', $limitations);

        return
            $this->authorizationChecker->isGranted($readAttribute) ||
            $this->authorizationChecker->isGranted($viewEmbedAttribute);
    }

    /**
     * Checks if the view is an embed one.
     * Uses either the controller action (embedAction), or the viewType (embed/embed-inline).
     *
     * @param array $parameters The ViewBuilder parameters array
     *
     * @return bool
     */
    private function isEmbed($parameters): bool
    {
        if ($parameters['_controller'] === 'ng_content:embedAction') {
            return true;
        }

        if (in_array($parameters['viewType'], ['embed', 'embed-inline'])) {
            return true;
        }

        return false;
    }
}
