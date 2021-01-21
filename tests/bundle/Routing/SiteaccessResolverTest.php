<?php


namespace Netgen\Bundle\EzPlatformSiteApiBundle\Tests\Routing;

use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\Repository\Values\Content\Location as CoreLocation;
use eZ\Publish\SPI\Persistence\Content\Handler as ContentHandler;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use eZ\Publish\SPI\Persistence\Handler;
use Netgen\Bundle\EzPlatformSiteApiBundle\Routing\SiteaccessResolver;
use eZ\Publish\API\Repository\Values\Content\Location;
use PHPUnit\Framework\TestCase;
use function array_filter;
use function array_key_exists;
use function array_pop;
use function explode;
use function in_array;

/**
 * @group siteaccess
 */
class SiteaccessResolverTest extends TestCase
{
    public function providerForTestResolve(): array
    {
        return [
            'Nothing matches the subtree, current siteaccess is used as a fallback' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ger'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => ['tree_root' =>  4],
                        'eng' => ['languages' => ['eng-GB']],
                        'ger' => ['languages' => ['ger-DE']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['ita-IT'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content is always available' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ger'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => ['tree_root' => 2],
                        'eng' => ['languages' => ['eng-GB']],
                        'ger' => ['languages' => ['ger-DE']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT'],
                            'alwaysAvailable' => true,
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content has a language that is allowed on it' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ger'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => ['tree_root' => 2],
                        'eng' => ['languages' => ['eng-GB', 'ita-IT']],
                        'ger' => ['languages' => ['ger-DE']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT'],
                        ],
                    ],
                ],
                'eng',
            ],
            'First siteaccess matching the subtree and allowing the Content with the most prioritized language,
             translation siteaccess is ignored 1' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'ger_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ger', 'ger_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ger'],
                        ],
                        'eng' => ['languages' => ['eng-GB']],
                        'ger' => ['languages' => ['ger-DE']],
                        'ger_mobile' => ['languages' => ['ger-DE']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ger-DE'],
                        ],
                    ],
                ],
                'ger',
            ],
            'First siteaccess matching the subtree and allowing the Content with the most prioritized language,
             translation siteaccess is ignored 2' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'ger_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ger', 'ger_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ger_mobile'],
                        ],
                        'eng' => ['languages' => ['eng-GB']],
                        'ger' => ['languages' => ['ger-DE']],
                        'ger_mobile' => ['languages' => ['ger-DE']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ger-DE'],
                        ],
                    ],
                ],
                'ger',
            ],
            'Current siteaccess matches the subtree and allows the Content,
            translation siteaccesses are not configured' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'fre'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'fre'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'fre' => ['languages' => ['fre-FR']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content has a language that has a configured
            translation siteaccess 1' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'fre'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'fre'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita', 'fre'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'fre' => ['languages' => ['fre-FR']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'prefer_translation_siteaccess' => true,
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'fre',
            ],
            'Current siteaccess matches the subtree and Content has a language that has a configured
            translation siteaccess 1 / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'fre'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'fre'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita', 'fre'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'fre' => ['languages' => ['fre-FR']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content has a language that has a configured
            translation siteaccess 2' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'fre'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'fre'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita', 'fre'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'ita-IT', 'fre-FR']],
                        'ita' => ['languages' => ['ita-IT']],
                        'fre' => ['languages' => ['fre-FR']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'prefer_translation_siteaccess' => true,
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'ita',
            ],
            'Current siteaccess matches the subtree and Content has a language that has a configured
            translation siteaccess 2 / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'fre'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'fre'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita', 'fre'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'ita-IT', 'fre-FR']],
                        'ita' => ['languages' => ['ita-IT']],
                        'fre' => ['languages' => ['fre-FR']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content has a language that has a configured
            translation siteaccess 3' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'ita_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'ita_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita', 'ita_mobile'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'ita_mobile' => ['languages' => ['ita-IT']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'prefer_translation_siteaccess' => true,
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'ita',
            ],
            'Current siteaccess matches the subtree and Content has a language that has a configured
            translation siteaccess 3 / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'ita_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'ita_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita', 'ita_mobile'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'ita_mobile' => ['languages' => ['ita-IT']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content matches multiple
            translation siteaccesses, their order is significant' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'ita_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'ita_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita_mobile', 'ita'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'ita_mobile' => ['languages' => ['ita-IT']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'prefer_translation_siteaccess' => true,
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'ita_mobile',
            ],
            'Current siteaccess matches the subtree and Content matches multiple
            translation siteaccesses, their order is significant / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'ita_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'ita_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita_mobile', 'ita'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'ita_mobile' => ['languages' => ['ita-IT']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and Content matches multiple
            translation siteaccesses, one of them is excluded' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'ita_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'ita_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita_mobile', 'ita'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'ita_mobile' => ['languages' => ['ita-IT']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'prefer_translation_siteaccess' => true,
                        'excluded_siteaccess_names' => ['ita_mobile'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'ita',
            ],
            'Current siteaccess matches the subtree and Content matches multiple
            translation siteaccesses, one of them is excluded / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita', 'ita_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ita', 'ita_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ita_mobile', 'ita'],
                        ],
                        'eng' => ['languages' => ['eng-GB', 'fre-FR', 'ita-IT']],
                        'ita' => ['languages' => ['ita-IT']],
                        'ita_mobile' => ['languages' => ['ita-IT']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'excluded_siteaccess_names' => ['ita_mobile'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'fre-FR'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Current siteaccess matches the subtree and it can show the Content in its
            most prioritized language' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'eng_mobile'],
                        'groups' => [
                            'frontend_group' => ['eng', 'eng_mobile'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng_mobile'],
                        ],
                        'eng' => ['languages' => ['eng-GB']],
                        'eng_mobile' => ['languages' => ['eng-GB']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['eng-GB'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Nothing matches, current siteaccess is returned as a fallback 1' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger'],
                        'groups' => [
                            'frontend_group' => ['eng', 'ger'],
                        ],
                    ],
                    'system' => [
                        'frontend_group' => [
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ger'],
                        ],
                        'eng' => ['languages' => ['eng-GB']],
                        'ger' => ['languages' => ['ger-DE']],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'ger',
            ],
            'Nothing matches, current siteaccess is returned as a fallback 2' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ger'],
                        ],
                        'ger' => [
                            'languages' => ['ger-DE'],
                            'tree_root' => 4,
                            'translation_siteaccesses' => ['eng', 'ger'],
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'eng',
            ],
            'Siteaccess matches the subtree and Content is always available' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['eng', 'ger'],
                        ],
                        'ger' => [
                            'languages' => ['ger-DE'],
                            'tree_root' => 4,
                            'translation_siteaccesses' => ['eng', 'ger'],
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                            'alwaysAvailable' => true,
                        ],
                    ],
                ],
                'eng',
            ],
            'Single siteaccess matching current siteaccess prioritized languages 1' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'ita'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'jpn-JP'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'jpn',
            ],
            'Single siteaccess matching current siteaccess prioritized languages 2' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'ita'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'ita',
            ],
            'First siteaccess matching current siteaccess prioritized languages 1' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'ita', 'ita_mobile'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                        'ita_mobile' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'ita',
            ],
            'First siteaccess matching current siteaccess prioritized languages 2' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'ita_mobile', 'ita'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                        'ita_mobile' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'ita_mobile',
            ],
            'First siteaccess matching current siteaccess prioritized languages 3' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'ger_other', 'ger_other_mobile'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'ger_other' => [
                            'languages' => ['ger-DE', 'jpn-JP', 'ita-IT'],
                            'tree_root' => 8,
                        ],
                        'ger_other_mobile' => [
                            'languages' => ['ger-DE', 'ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'ger_other',
            ],
            'First siteaccess matching current siteaccess prioritized languages 4' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'fre', 'fre_mobile'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR', 'ger-DE', 'jpn-JP', 'ita-IT'],
                            'tree_root' => 8,
                        ],
                        'fre_mobile' => [
                            'languages' => ['fre-FR', 'ger-DE', 'ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'fre',
            ],
            'Order of siteaccesses is significant 1' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'jpn', 'fre'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'fre-FR'],
                        ],
                    ],
                ],
                'jpn',
            ],
            'Order of siteaccesses is significant 2' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'fre', 'jpn'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE'],
                            'tree_root' => 4,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 8,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'fre-FR'],
                        ],
                    ],
                ],
                'fre',
            ],
            'Nothing matches, current siteaccess is returned as a fallback 3' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'ita', 'fre'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE'],
                            'tree_root' => 4,
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['jpn-JP', 'port-PT'],
                        ],
                    ],
                ],
                'ita',
            ],
            'First siteaccess matching current siteaccess prioritized languages
            (translation siteaccess is ignored)' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'ita', 'por'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT', 'por-PT'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['por-PT', 'ita-IT'],
                        ],
                    ],
                ],
                'por',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess
            prioritized languages' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'prefer_translation_siteaccess' => true,
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT'],
                        ],
                    ],
                ],
                'ita',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess
            prioritized languages / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT'],
                        ],
                    ],
                ],
                'por',
            ],
            'First siteaccess in siteaccess list matching available languages 1' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'jpn'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                        ],
                        'ger' => [
                            'languages' => ['ger-DE'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['jpn-JP', 'ita-IT'],
                        ],
                    ],
                ],
                'jpn',
            ],
            'First siteaccess in siteaccess list matching available languages 2' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'jpn'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                        ],
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['fre-FR', 'ita-IT'],
                        ],
                    ],
                ],
                'ger',
            ],
            'First siteaccess in siteaccess list matching available languages 3' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'jpn', 'fre'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                        ],
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['fre-FR', 'ita-IT'],
                        ],
                    ],
                ],
                'ger',
            ],
            'First siteaccess in siteaccess list matching available languages with excluded siteaccess' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'jpn', 'fre'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                        ],
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'excluded_siteaccess_names' => ['fre'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['fre-FR', 'ita-IT'],
                        ],
                    ],
                ],
                'ger',
            ],
            'First siteaccess in siteaccess list matching available languages with excluded siteaccess group' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ger', 'jpn', 'fre'],
                        'groups' => [
                            'excluded_group' => ['fre'],
                        ],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 2,
                        ],
                        'ger' => [
                            'languages' => ['ger-DE', 'ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                        'fre' => [
                            'languages' => ['fre-FR'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'excluded_siteaccess_group_names' => ['excluded_group'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['fre-FR', 'ita-IT'],
                        ],
                    ],
                ],
                'ger',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess prioritized languages
            with excluded siteaccess 1' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita', 'jpn'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita', 'jpn'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'prefer_translation_siteaccess' => true,
                        'excluded_siteaccess_names' => ['ita'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'jpn-JP'],
                        ],
                    ],
                ],
                'jpn',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess prioritized languages
            with excluded siteaccess 1 / prefer translation siteaccess off' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita', 'jpn'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita', 'jpn'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'excluded_siteaccess_names' => ['ita'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'jpn-JP'],
                        ],
                    ],
                ],
                'por',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess prioritized languages
            with excluded siteaccess 2' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita', 'jpn'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita', 'jpn'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'prefer_translation_siteaccess' => true,
                        'excluded_siteaccess_names' => ['jpn'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'jpn-JP'],
                        ],
                    ],
                ],
                'ita',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess prioritized languages
            with excluded siteaccess group 1' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita', 'jpn'],
                        'groups' => [
                            'excluded_group' => ['ita'],
                        ],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita', 'jpn'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'prefer_translation_siteaccess' => true,
                        'excluded_siteaccess_group_names' => ['excluded_group'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'jpn-JP'],
                        ],
                    ],
                ],
                'jpn',
            ],
            'Translation siteaccess of the first siteaccess matching current siteaccess prioritized languages
            with excluded siteaccess group 2' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'por', 'ita', 'jpn'],
                        'groups' => [
                            'excluded_group' => ['jpn'],
                        ],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ita-IT', 'jpn-JP'],
                            'tree_root' => 2,
                            'translation_siteaccesses' => ['ita', 'jpn'],
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 2,
                        ],
                        'jpn' => [
                            'languages' => ['jpn-JP'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'prefer_translation_siteaccess' => true,
                        'excluded_siteaccess_group_names' => ['excluded_group'],
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['ita-IT', 'jpn-JP'],
                        ],
                    ],
                ],
                'ita',
            ],
            'Siteaccess is selected by the current siteaccess prioritized languages first, siteaccess list and
            available languages second 1' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'ger_other', 'por'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'ger_other' => [
                            'languages' => ['ger-DE', 'jpn-JP'],
                            'tree_root' => 2,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ger-DE'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['por-PT', 'jpn-JP'],
                        ],
                    ],
                ],
                'ger_other',
            ],
            'Siteaccess is selected by the current siteaccess prioritized languages first, siteaccess list and
            available languages second 2' => [
                [
                    'siteaccess' => [
                        'list' => ['ger', 'ger_other', 'por'],
                    ],
                    'system' => [
                        'ger' => [
                            'languages' => ['ger-DE', 'por-PT'],
                            'tree_root' => 4,
                        ],
                        'ger_other' => [
                            'languages' => ['ger-DE', 'jpn-JP'],
                            'tree_root' => 2,
                        ],
                        'por' => [
                            'languages' => ['por-PT', 'ger-DE'],
                            'tree_root' => 2,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'ger',
                        'location' => [
                            'pathString' => '/1/2/42/',
                            'languageCodes' => ['por-PT'],
                        ],
                    ],
                ],
                'por',
            ],
            'Location is in the configured external subtree' => [
                [
                    'siteaccess' => [
                        'list' => ['eng', 'ita'],
                    ],
                    'system' => [
                        'eng' => [
                            'languages' => ['eng-GB'],
                            'tree_root' => 4,
                        ],
                        'ita' => [
                            'languages' => ['ita-IT'],
                            'tree_root' => 8,
                        ],
                    ],
                    '_context' => [
                        'current_siteaccess' => 'eng',
                        'external_subtree_roots' => [8],
                        'location' => [
                            'pathString' => '/1/2/8/42/',
                            'languageCodes' => ['ita-IT'],
                        ],
                    ],
                ],
                'eng',
            ],
        ];
    }

    /**
     * @dataProvider providerForTestResolve
     *
     * @throws \Exception
     */
    public function testResolve(array $data, string $expectedSiteaccessName): void
    {
        $siteaccessResolver = $this->getSiteaccessResolverUnderTest($data);
        $location = $this->getMockedLocation($data);

        self::assertSame($expectedSiteaccessName, $siteaccessResolver->resolve($location));
        self::assertSame($expectedSiteaccessName, $siteaccessResolver->resolve($location));
    }

    protected function getMockedLocation(array $data): Location
    {
        $data = $data['_context']['location'];
        $pathIds = array_filter(explode('/', $data['pathString']));

        return new CoreLocation([
            'id' => array_pop($pathIds),
            'pathString' => $data['pathString'],
            'contentInfo' => new ContentInfo([
                'id' => 24,
                'alwaysAvailable' => $data['alwaysAvailable'] ?? false,
            ]),
        ]);
    }

    protected function getSiteaccessResolverUnderTest(array $data): SiteaccessResolver
    {
        $siteaccessResolver = new SiteaccessResolver(
            $this->persistenceHandlerMock($data),
            $this->getExcludedSiteaccessNames($data),
            $this->getExcludedSiteaccessGroupNames($data),
            5
        );

        $siteaccessResolver->setConfigResolver($this->getConfigResolverMock($data));
        $siteaccessResolver->setSiteaccess($this->getSiteaccess($data));
        $siteaccessResolver->setSiteaccessGroupsBySiteaccess($this->getSiteaccessGroupsBySiteaccess($data));
        $siteaccessResolver->setSiteaccessList($this->getSiteaccessList($data));

        return $siteaccessResolver;
    }

    protected function persistenceHandlerMock(array $data): Handler
    {
        $versionInfo = new VersionInfo([
            'languageCodes' => $data['_context']['location']['languageCodes'],
        ]);

        $contentHandlerMock = $this->createMock(ContentHandler::class);
        $contentHandlerMock->method('loadVersionInfo')->willReturn($versionInfo);

        $persistenceHandler = $this->createMock(Handler::class);
        $persistenceHandler->method('contentHandler')->willReturn($contentHandlerMock);

        return $persistenceHandler;
    }

    protected function getExcludedSiteaccessNames(array $data): array
    {
        return $data['_context']['excluded_siteaccess_names'] ?? [];
    }

    protected function getExcludedSiteaccessGroupNames(array $data): array
    {
        return $data['_context']['excluded_siteaccess_group_names'] ?? [];
    }

    protected function getConfigResolverMock(array $data): ConfigResolverInterface
    {
        $configResolver = $this->createMock(ConfigResolverInterface::class);
        $hasParameterValueMap = $this->getConfigResolverHasParameterReturnValueMap($data);
        $getParameterValueMap = $this->getConfigResolverGetParameterReturnValueMap($data);

        $configResolver->method('hasParameter')->will(self::returnValueMap($hasParameterValueMap));
        $configResolver->method('getParameter')->will(self::returnValueMap($getParameterValueMap));

        return $configResolver;
    }

    protected function getConfigResolverHasParameterReturnValueMap(array $data): array
    {
        $valueMap = [];
        $siteaccessConfigMap = $this->getSiteaccessConfigMap($data);

        foreach ($siteaccessConfigMap as $siteaccess => $config) {
            $valueMap[] = ['translation_siteaccesses', null, $siteaccess, array_key_exists('translation_siteaccesses', $config)];
        }

        return $valueMap;
    }

    protected function getConfigResolverGetParameterReturnValueMap(array $data): array
    {
        $siteaccessConfigMap = $this->getSiteaccessConfigMap($data);
        $valueMap = [
            [
                'ng_cross_siteaccess_routing_prefer_translation_siteaccess',
                null,
                null,
                $data['_context']['prefer_translation_siteaccess'] ?? false
            ],
            [
                'ng_cross_siteaccess_routing_external_subtree_roots',
                null,
                null,
                $data['_context']['external_subtree_roots'] ?? []
            ]
        ];

        foreach ($siteaccessConfigMap as $siteaccess => $config) {
            $valueMap[] = ['languages', null, $siteaccess, $config['languages'] ?? []];
            $valueMap[] = ['translation_siteaccesses', null, $siteaccess, $config['translation_siteaccesses'] ?? []];
            $valueMap[] = ['content.tree_root.location_id', null, $siteaccess, $config['tree_root'] ?? null];
        }

        return $valueMap;
    }

    protected function getSiteaccessConfigMap(array $data): array
    {
        $siteaccesses = $data['siteaccess']['list'];
        $map = [];

        foreach ($siteaccesses as $siteaccess) {
            $group = $this->getSiteaccessGroupBySiteaccess($siteaccess, $data);
            $siteaccessConfig = $data['system'][$siteaccess] ?? [];
            $groupConfig = $data['system'][$group] ?? [];

            $map[$siteaccess] = $siteaccessConfig + $groupConfig;
        }

        return $map;
    }

    protected function getSiteaccessGroupBySiteaccess(string $siteaccess, array $data): ?string
    {
        $groups = $data['siteaccess']['groups'] ?? [];

        foreach ($groups as $group => $groupSiteaccesses) {
            if (in_array($siteaccess, $groupSiteaccesses, true)) {
                return $group;
            }
        }

        return null;
    }

    protected function getSiteaccess(array $data): SiteAccess
    {
        return new SiteAccess($data['_context']['current_siteaccess']);
    }

    protected function getSiteaccessGroupsBySiteaccess(array $data): array
    {
        $map = [];
        $groups = $data['siteaccess']['groups'] ?? [];

        foreach ($groups as $group => $siteaccesses) {
            foreach ($siteaccesses as $siteaccess) {
                $map[$siteaccess][] = $group;
            }
        }

        return $map;
    }

    protected function getSiteaccessList(array $data): array
    {
        return $data['siteaccess']['list'];
    }
}
