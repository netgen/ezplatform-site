<?php

namespace Netgen\EzPlatformSiteApi\API;

/**
 * Load service provides methods for loading entities by their ID.
 */
interface LoadService
{
    /**
     * Loads Content object for the given $contentId.
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     *
     * @param string|int $contentId
     * @param int $versionNo
     * @param string $languageCode
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    public function loadContent($contentId, $versionNo = null, $languageCode = null);

    /**
     * Loads Content object for the given $remoteId.
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     *
     * @param string|int $remoteId
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Content
     */
    public function loadContentByRemoteId($remoteId);

    /**
     * Loads Location object for the given $locationId.
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     *
     * @param string|int $locationId
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    public function loadLocation($locationId);

    /**
     * Loads Location object for the given $remoteId.
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     *
     * @param string|int $remoteId
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Location
     */
    public function loadLocationByRemoteId($remoteId);

    /**
     * @deprecated since version 2.1, to be removed in 3.0. Use loadLocation() instead.
     *
     * Loads Node object for the given $locationId.
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     *
     * @param string|int $locationId
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Node
     */
    public function loadNode($locationId);

    /**
     * @deprecated since version 2.1, to be removed in 3.0. Use loadLocationByRemoteId() instead.
     *
     * Loads Node object for the given $remoteId.
     *
     * @throws \Netgen\EzPlatformSiteApi\API\Exceptions\TranslationNotMatchedException
     *
     * @param string|int $remoteId
     *
     * @return \Netgen\EzPlatformSiteApi\API\Values\Node
     */
    public function loadNodeByRemoteId($remoteId);
}
