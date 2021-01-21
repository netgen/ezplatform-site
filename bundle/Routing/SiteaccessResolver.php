<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Routing;

use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\SPI\Persistence\Handler;
use function array_fill_keys;
use function array_keys;
use function array_map;
use function in_array;
use function reset;

/**
 * todo group boxes
 * @internal do not depend on this service, it can be changed without warning
 */
class SiteaccessResolver
{
    private $persistenceHandler;
    private $excludedSiteaccessSet;
    private $excludedSiteaccessGroupSet;
    private $cache = [];

    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var SiteAccess
     */
    private $currentSiteaccess;

    /**
     * @var array
     */
    private $siteaccesses;

    /**
     * @var array
     */
    private $siteaccessGroupsBySiteaccess;

    /**
     * @var int
     */
    private $recursionLimit;

    public function __construct(
        Handler $persistenceHandler,
        array $excludedSiteaccesses,
        array $excludedSiteaccessGroups,
        int $recursionLimit
    ) {
        $this->persistenceHandler = $persistenceHandler;
        $this->excludedSiteaccessSet = array_fill_keys($excludedSiteaccesses, true);
        $this->excludedSiteaccessGroupSet = array_fill_keys($excludedSiteaccessGroups, true);
        $this->recursionLimit = $recursionLimit;
    }

    /**
     * @param ConfigResolverInterface $configResolver
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    public function setSiteaccess(SiteAccess $currentSiteAccess = null): void
    {
        $this->currentSiteaccess = $currentSiteAccess;
    }

    public function setSiteaccessList(array $siteaccesses): void
    {
        $this->siteaccesses = $siteaccesses;
    }

    public function setSiteaccessGroupsBySiteaccess(array $siteaccessGroupsBySiteaccess): void
    {
        $this->siteaccessGroupsBySiteaccess = $siteaccessGroupsBySiteaccess;
    }

    /**
     * @throws \Exception
     */
    public function resolve(Location $location): string
    {
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (isset($this->cache['resolve'][$currentSiteaccess][$location->id])) {
            return $this->cache['resolve'][$currentSiteaccess][$location->id];
        }

        $siteaccess = $this->internalResolve($location);
        $this->cache['resolve'][$currentSiteaccess][$location->id] = $siteaccess;

        return $siteaccess;
    }

    /**
     * @throws \Exception
     */
    private function internalResolve(Location $location): string
    {
        $siteaccessSet = $this->getSiteaccessSet($location);
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (empty($siteaccessSet)) {
            return $currentSiteaccess;
        }

        if ($this->isInExternalSubtree($location)) {
            return $currentSiteaccess;
        }

        if (isset($siteaccessSet[$currentSiteaccess])) {
            $match = $this->cachedMatchFromSiteaccess($location, $currentSiteaccess);

            if ($match !== null) {
                return $match;
            }
        }

        $currentPrioritizedLanguages = $this->getPrioritizedLanguages($currentSiteaccess);

        foreach ($currentPrioritizedLanguages as $language) {
            $match = $this->cachedMatchByPrioritizedLanguage($location, $language);

            if ($match !== null) {
                return $match;
            }
        }

        foreach (array_keys($siteaccessSet) as $siteaccess) {
            $match = $this->cachedMatchFromSiteaccess($location, $siteaccess);

            if ($match !== null) {
                return $match;
            }
        }

        if (isset($siteaccessSet[$currentSiteaccess])) {
            return $currentSiteaccess;
        }

        return array_key_first($siteaccessSet);
    }

    private function isInExternalSubtree(Location $location): bool
    {
        if (isset($this->cache['in_external_subtree'][$location->id])) {
            return $this->cache['in_external_subtree'][$location->id];
        }

        $rootSet = $this->getExternalSubtreeRootSet();

        foreach ($location->path as $id) {
            if (isset($rootSet[(int) $id])) {
                return $this->cache['in_external_subtree'][$location->id] = true;
            }
        }

        return $this->cache['in_external_subtree'][$location->id] = false;
    }

    /**
     * @return int[]
     */
    private function getExternalSubtreeRootSet(): array
    {
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (isset($this->cache['external_subtree_root_set'][$currentSiteaccess])) {
            return $this->cache['external_subtree_root_set'][$currentSiteaccess];
        }

        $roots = $this->configResolver->getParameter('ng_cross_siteaccess_routing_external_subtree_roots');

        return $this->cache['external_subtree_root_set'][$currentSiteaccess] = array_fill_keys($roots, true);
    }

    /**
     * @throws \Exception
     */
    private function cachedMatchByPrioritizedLanguage(Location $location, string $language): ?string
    {
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (isset($this->cache['match_by_prioritized'][$currentSiteaccess][$location->id][$language])) {
            return $this->cache['match_by_prioritized'][$currentSiteaccess][$location->id][$language] ?: null;
        }

        $match = $this->matchByPrioritizedLanguage($location, $language);
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $this->cache['match_by_prioritized'][$currentSiteaccess][$location->id][$language] = $match ?? false;

        return $match;
    }

    /**
     * @throws \Exception
     */
    private function matchByPrioritizedLanguage(Location $location, string $language, int $position = 0): ?string
    {
        $recurse = false;
        $siteaccessSet = $this->getSiteaccessSet($location);

        foreach (array_keys($siteaccessSet) as $siteaccess) {
            $prioritizedLanguages = $this->getPrioritizedLanguages($siteaccess);
            $positionedLanguage = $prioritizedLanguages[$position] ?? null;
            $recurse = $recurse || isset($prioritizedLanguages[$position + 1]);

            if ($language !== $positionedLanguage) {
                continue;
            }

            $match = $this->cachedMatchFromSiteaccess($location, $siteaccess);

            if ($match !== null) {
                return $match;
            }
        }

        $nextPosition = $position + 1;

        if (!$recurse || $nextPosition >= $this->recursionLimit) {
            return null;
        }

        return $this->matchByPrioritizedLanguage($location, $language, $nextPosition);
    }

    /**
     * @throws \Exception
     */
    private function cachedMatchFromSiteaccess(Location $location, string $siteaccess): ?string
    {
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (isset($this->cache['match_from_siteaccess'][$currentSiteaccess][$siteaccess][$location->id])) {
            return $this->cache['match_from_siteaccess'][$currentSiteaccess][$siteaccess][$location->id] ?: null;
        }

        $match = $this->matchFromSiteaccess($location, $siteaccess);
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $this->cache['match_from_siteaccess'][$currentSiteaccess][$siteaccess][$location->id] = $match ?? false;

        return $match;
    }

    /**
     * @throws \Exception
     */
    private function matchFromSiteaccess(Location $location, string $siteaccess): ?string
    {
        if (!$this->canShow($siteaccess, $location)) {
            return null;
        }

        if ($this->preferTranslationSiteaccess()) {
            if ($this->canShowInPrimaryLanguage($siteaccess, $location)) {
                return $siteaccess;
            }

            $match = $this->cachedMatchTranslationSiteaccess($location, $siteaccess);

            if ($match !== null) {
                return $match;
            }
        }

        return $siteaccess;
    }

    /**
     * @throws \Exception
     */
    private function canShowInPrimaryLanguage(string $siteaccess, Location $location): bool
    {
        if (isset($this->cache['can_show_primary'][$siteaccess][$location->id])) {
            return $this->cache['can_show_primary'][$siteaccess][$location->id];
        }

        $availableLanguageSet = $this->getLanguageSet($location);
        $primaryLanguage = $this->getPrimaryLanguage($siteaccess);
        $canShow = isset($availableLanguageSet[$primaryLanguage]);

        return $this->cache['can_show_primary'][$siteaccess][$location->id] = $canShow;
    }

    /**
     * @throws \Exception
     */
    private function cachedMatchTranslationSiteaccess(Location $location, string $siteaccess): ?string
    {
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (isset($this->cache['match_translation_siteaccess'][$currentSiteaccess][$siteaccess][$location->id])) {
            return $this->cache['match_translation_siteaccess'][$currentSiteaccess][$siteaccess][$location->id] ?: null;
        }

        $match = $this->matchTranslationSiteaccess($location, $siteaccess);
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $this->cache['match_translation_siteaccess'][$currentSiteaccess][$siteaccess][$location->id] = $match ?? false;

        return $match;
    }

    /**
     * @throws \Exception
     */
    private function matchTranslationSiteaccess(Location $location, string $siteaccess): ?string
    {
        $availableLanguageSet = $this->getLanguageSet($location);
        $currentPrioritizedLanguages = $this->getPrioritizedLanguages($this->currentSiteaccess->name);

        foreach ($currentPrioritizedLanguages as $language) {
            if (!isset($availableLanguageSet[$language])) {
                continue;
            }

            $match = $this->matchTranslationSiteaccessByLanguage($siteaccess, $language);

            if ($match !== null) {
                return $match;
            }
        }

        $translationSiteaccesses = $this->getTranslationSiteaccesses($siteaccess);

        foreach ($translationSiteaccesses as $translationSiteaccess) {
            if ($this->isSiteaccessExcluded($translationSiteaccess)) {
                continue;
            }

            $primaryLanguage = $this->getPrimaryLanguage($translationSiteaccess);

            if (isset($availableLanguageSet[$primaryLanguage])) {
                return $translationSiteaccess;
            }
        }

        return null;
    }

    private function matchTranslationSiteaccessByLanguage(string $siteaccess, string $language): ?string
    {
        if (isset($this->cache['translation_siteaccess_by_language'][$siteaccess][$language])) {
            return $this->cache['translation_siteaccess_by_language'][$siteaccess][$language] ?: null;
        }

        $translationSiteaccesses = $this->getTranslationSiteaccesses($siteaccess);

        foreach ($translationSiteaccesses as $translationSiteaccess) {
            if ($this->isSiteaccessExcluded($translationSiteaccess)) {
                continue;
            }

            $primaryLanguage = $this->getPrimaryLanguage($translationSiteaccess);

            if ($language === $primaryLanguage) {
                return $this->cache['translation_siteaccess_by_language'][$siteaccess][$language] = $translationSiteaccess;
            }
        }

        $this->cache['translation_siteaccess_by_language'][$siteaccess][$language] = false;

        return null;
    }

    private function preferTranslationSiteaccess(): bool
    {
        $currentSiteaccess = $this->currentSiteaccess->name;

        if (isset($this->cache['prefer_translation_siteaccess'][$currentSiteaccess])) {
            return $this->cache['prefer_translation_siteaccess'][$currentSiteaccess];
        }

        $value = $this->configResolver->getParameter('ng_cross_siteaccess_routing_prefer_translation_siteaccess');

        return $this->cache['prefer_translation_siteaccess'][$currentSiteaccess] = $value;
    }

    /**
     * @throws \Exception
     */
    private function canShow(string $siteaccess, Location $location): bool
    {
        if (isset($this->cache['can_show'][$siteaccess][$location->id])) {
            return $this->cache['can_show'][$siteaccess][$location->id];
        }

        if ($location->contentInfo->alwaysAvailable) {
            return $this->cache['can_show'][$siteaccess][$location->id] = true;
        }

        $prioritizedLanguages = $this->getPrioritizedLanguages($siteaccess);
        $availableLanguageSet = $this->getLanguageSet($location);

        foreach ($prioritizedLanguages as $language) {
            if (isset($availableLanguageSet[$language])) {
                return $this->cache['can_show'][$siteaccess][$location->id] = true;
            }
        }

        return $this->cache['can_show'][$siteaccess][$location->id] = false;
    }

    /**
     * @throws \Exception
     *
     * @return string[]
     */
    private function getLanguageSet(Location $location): array
    {
        if (!isset($this->cache['location_available_language_set'][$location->id])) {
            $this->cache['location_available_language_set'][$location->id] = array_fill_keys(
                $this->persistenceHandler->contentHandler()->loadVersionInfo(
                    $location->contentId,
                    $location->contentInfo->currentVersionNo
                )->languageCodes,
                true
            );
        }

        return $this->cache['location_available_language_set'][$location->id];
    }

    /**
     * @return string
     */
    private function getPrimaryLanguage(string $siteaccess): string
    {
        if (isset($this->cache['primary_language'][$siteaccess])) {
            return $this->cache['primary_language'][$siteaccess];
        }

        $prioritizedLanguages = $this->getPrioritizedLanguages($siteaccess);
        $this->cache['primary_language'][$siteaccess] = reset($prioritizedLanguages);

        return $this->cache['primary_language'][$siteaccess];
    }

    /**
     * @return string[]
     */
    private function getPrioritizedLanguages(string $siteaccess): array
    {
        if (!isset($this->cache['prioritized_languages'][$siteaccess])) {
            $this->cache['prioritized_languages'][$siteaccess] = $this->configResolver->getParameter(
                'languages',
                null,
                $siteaccess
            );
        }

        return $this->cache['prioritized_languages'][$siteaccess];
    }

    /**
     * @return string[]
     */
    private function getTranslationSiteaccesses(string $siteaccess): array
    {
        if (isset($this->cache['translation_siteaccesses'][$siteaccess])) {
            return $this->cache['translation_siteaccesses'][$siteaccess];
        }

        $translationSiteaccesses = [];

        if ($this->configResolver->hasParameter('translation_siteaccesses', null, $siteaccess)) {
            $translationSiteaccesses = $this->configResolver->getParameter(
                'translation_siteaccesses',
                null,
                $siteaccess
            );
        }

        return $this->cache['translation_siteaccesses'][$siteaccess] = $translationSiteaccesses;
    }

    private function getSiteaccessSet(Location $location): array
    {
        if (isset($this->cache['location_siteaccess_set'][$location->id])) {
            return $this->cache['location_siteaccess_set'][$location->id];
        }

        $ancestorAndSelfLocationIds = array_map('\intval', $location->path);
        $this->initializeSiteaccessRootLocationIdMap();
        $siteaccessSet = [];

        foreach ($this->cache['siteaccess_root_location_id_map'] as $siteaccess => $rootLocationId) {
            if (in_array($rootLocationId, $ancestorAndSelfLocationIds, true)) {
                $siteaccessSet[$siteaccess] = true;
            }
        }

        return $this->cache['location_siteaccess_set'][$location->id] = $siteaccessSet;
    }

    private function initializeSiteaccessRootLocationIdMap(): void
    {
        if (isset($this->cache['siteaccess_root_location_id_map'])) {
            return;
        }

        $this->cache['siteaccess_root_location_id_map'] = [];

        foreach ($this->siteaccesses as $siteaccess) {
            if ($this->isSiteaccessExcluded($siteaccess)) {
                continue;
            }

            $rootLocationId = $this->configResolver->getParameter(
                'content.tree_root.location_id',
                null,
                $siteaccess
            );

            $this->cache['siteaccess_root_location_id_map'][$siteaccess] = $rootLocationId;
        }
    }

    private function isSiteaccessExcluded(string $siteaccess): bool
    {
        if (isset($this->cache['siteaccess_excluded'][$siteaccess])) {
            return $this->cache['siteaccess_excluded'][$siteaccess];
        }

        if (isset($this->excludedSiteaccessSet[$siteaccess])) {
            return $this->cache['siteaccess_excluded'][$siteaccess] = true;
        }

        $siteaccessGroups = $this->siteaccessGroupsBySiteaccess[$siteaccess] ?? [];

        foreach ($siteaccessGroups as $siteaccessGroup) {
            if (isset($this->excludedSiteaccessGroupSet[$siteaccessGroup])) {
                return $this->cache['siteaccess_excluded'][$siteaccess] = true;
            }
        }

        return $this->cache['siteaccess_excluded'][$siteaccess] = false;
    }
}
