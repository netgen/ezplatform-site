<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Templating\Twig\Extension;

use eZ\Publish\Core\MVC\Symfony\View\Builder\ContentViewBuilder;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ViewRenderer;

/**
 * Twig extension runtime for Site API embedded content view rendering.
 */
class EzEmbeddedContentViewRuntime
{
    /**
     * @var \eZ\Publish\Core\MVC\Symfony\View\Builder\ContentViewBuilder
     */
    private $viewBuilder;

    /**
     * @var \Netgen\Bundle\EzPlatformSiteApiBundle\View\ViewRenderer
     */
    private $viewRenderer;

    public function __construct(
        ContentViewBuilder $viewBuilder,
        ViewRenderer $viewRenderer
    ) {
        $this->viewBuilder = $viewBuilder;
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * Renders the HTML for a given $content.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function renderEmbeddedContentView(string $viewType, array $parameters = []): string
    {
        $baseParameters = [
            'viewType' => $viewType,
            'layout' => false,
            '_controller' => 'ez_content:embedAction',
        ];

        $view = $this->viewBuilder->buildView($baseParameters + $parameters);

        return $this->viewRenderer->render($view, $parameters, false);
    }
}
