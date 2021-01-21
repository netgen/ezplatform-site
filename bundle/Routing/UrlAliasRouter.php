<?php


namespace Netgen\Bundle\EzPlatformSiteApiBundle\Routing;

use eZ\Bundle\EzPublishCoreBundle\Routing\UrlAliasRouter as BaseUrlAliasRouter;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use InvalidArgumentException;
use LogicException;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\ContentInfo;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use function strlen as strlen;
use function strpos as strpos;
use function substr as substr;

class UrlAliasRouter extends BaseUrlAliasRouter
{
    public const OVERRIDE_VIEW_ACTION = 'ng_content:viewAction';

    private $currentSiteaccess;
    private $siteaccessResolver;

    public function __construct(
        LocationService $locationService,
        URLAliasService $urlAliasService,
        ContentService $contentService,
        UrlAliasGenerator $generator,
        SiteaccessResolver $siteaccessResolver,
        RequestContext $requestContext,
        LoggerInterface $logger = null
    ) {
        parent::__construct($locationService, $urlAliasService, $contentService, $generator, $requestContext, $logger);

        $this->siteaccessResolver = $siteaccessResolver;
    }

    public function setSiteaccess(SiteAccess $currentSiteAccess = null): void
    {
        $this->currentSiteaccess = $currentSiteAccess;
    }

    public function matchRequest(Request $request): array
    {
        $parameters = parent::matchRequest($request);
        $overrideViewAction = $this->configResolver->getParameter(
            'override_url_alias_view_action',
            'netgen_ez_platform_site_api'
        );

        if ($overrideViewAction) {
            $parameters['_controller'] = self::OVERRIDE_VIEW_ACTION;
        }

        return $parameters;
    }

    /**
     * @inheritdoc
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @throws \Exception
     */
    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $location = $this->resolveLocation($name, $parameters);
        $isCrossSiteaccessRoutingEnabled = $this->configResolver->getParameter('ng_cross_siteaccess_routing');

        if (!isset($parameters['siteaccess']) && $isCrossSiteaccessRoutingEnabled) {
            return $this->crossSiteaccessGenerate($location, $parameters, $referenceType);
        }

        return parent::generate($location, $parameters, $referenceType);
    }

    /**
     * @throws \Exception
     */
    private function crossSiteaccessGenerate(APILocation $location, array $parameters, int $referenceType): string
    {
        $siteaccessName = $this->siteaccessResolver->resolve($location);

        if ($siteaccessName === $this->currentSiteaccess->name) {
            return parent::generate($location, $parameters, $referenceType);
        }

        $parameters['siteaccess'] = $siteaccessName;

        $url = parent::generate($location, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

        if ($referenceType === UrlGeneratorInterface::RELATIVE_PATH) {
            $host = $this->requestContext->getHost();
            $hostLength = strlen($host);

            if (strpos($url, $host) === 0) {
                return substr($url, $hostLength);
            }
        }

        return $url;
    }

    public function supports($name): bool
    {
        return
            $name instanceof Content
            || $name instanceof ContentInfo
            || $name instanceof Location
            || parent::supports($name);
    }

    /**
     * @param mixed $name
     * @param mixed $parameters
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     */
    private function resolveLocation($name, $parameters): APILocation
    {
        if ($name instanceof Location) {
            return $name->innerLocation;
        }

        if ($name instanceof APILocation) {
            return $name;
        }

        if ($name instanceof Content || $name instanceof ContentInfo) {
            if (!$name->mainLocation instanceof Location) {
                throw new LogicException(
                    'Cannot generate an UrlAlias route for Content without the main Location'
                );
            }

            return $name->mainLocation->innerLocation;
        }

        if ($name !== self::URL_ALIAS_ROUTE_NAME) {
            throw new RouteNotFoundException('Could not match route');
        }

        return $this->resolveLocationFromParameters($parameters);
    }

    /**
     * @return \eZ\Publish\API\Repository\Values\Content\Location
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    private function resolveLocationFromParameters(array $parameters): APILocation
    {
        if (isset($parameters['location']) && $parameters['location'] instanceof APILocation) {
            return $parameters['location'];
        }

        if (isset($parameters['locationId'])) {
            return $this->locationService->loadLocation($parameters['locationId']);
        }

        if (isset($parameters['contentId'])) {
            $contentInfo = $this->contentService->loadContentInfo($parameters['contentId']);

            if ($contentInfo->mainLocationId === null) {
                throw new LogicException(
                    'Cannot generate an UrlAlias route for content without the main Location'
                );
            }

            return $this->locationService->loadLocation($contentInfo->mainLocationId);
        }

        throw new InvalidArgumentException(
            'When generating an UrlAlias route, either "location", "locationId" or "contentId" parameter must be provided'
        );
    }
}
