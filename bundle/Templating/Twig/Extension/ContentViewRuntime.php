<?php

declare(strict_types=1);

namespace Netgen\Bundle\EzPlatformSiteApiBundle\Templating\Twig\Extension;

use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\Values\Content\Content as APIContent;
use eZ\Publish\API\Repository\Values\Content\Location as APILocation;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\MVC\Symfony\View\Renderer;
use LogicException;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\Builder\ContentViewBuilder;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Netgen\EzPlatformSiteApi\API\Values\Content;
use Netgen\EzPlatformSiteApi\API\Values\Location;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Twig extension runtime for content rendering (view).
 */
class ContentViewRuntime
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface
     */
    private $controllerResolver;

    /**
     * @var \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface
     */
    private $argumentResolver;

    /**
     * @var \Netgen\Bundle\EzPlatformSiteApiBundle\View\Builder\ContentViewBuilder
     */
    private $viewBuilder;

    /**
     * @var \eZ\Publish\Core\MVC\Symfony\View\Renderer
     */
    private $viewRenderer;

    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    private $locationService;

    /**
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param \Symfony\Component\HttpKernel\Controller\ControllerResolverInterface $controllerResolver
     * @param \Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface $argumentResolver
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\View\Builder\ContentViewBuilder $viewBuilder
     * @param \eZ\Publish\Core\MVC\Symfony\View\Renderer $viewRenderer
     * @param \eZ\Publish\API\Repository\LocationService $locationService
     */
    public function __construct(
        RequestStack $requestStack,
        ControllerResolverInterface $controllerResolver,
        ArgumentResolverInterface $argumentResolver,
        ContentViewBuilder $viewBuilder,
        Renderer $viewRenderer,
        LocationService $locationService
    ) {
        $this->requestStack = $requestStack;
        $this->controllerResolver = $controllerResolver;
        $this->argumentResolver = $argumentResolver;
        $this->viewBuilder = $viewBuilder;
        $this->viewRenderer = $viewRenderer;
        $this->locationService = $locationService;
    }

    /**
     * Renders the HTML for a given $content.
     *
     * Note that this is experimental. Please report any issues on https://github.com/netgen/ezplatform-site-api/issues
     *
     * @param \eZ\Publish\API\Repository\Values\ValueObject $contentOrLocation
     * @param string $viewType
     * @param array $parameters
     * @param bool $layout
     *
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @throws \eZ\Publish\Core\Base\Exceptions\UnauthorizedException
     *
     * @return string The HTML markup
     */
    public function renderContentView(
        ValueObject $contentOrLocation,
        string $viewType,
        array $parameters = [],
        bool $layout = false
    ): string {
        $content = $this->getContent($contentOrLocation);

        $view = $this->viewBuilder->buildView([
            'content' => $content,
            'location' => $this->getLocation($contentOrLocation),
            'viewType' => $viewType,
            'layout' => $layout,
            '_controller' => 'ng_content:viewAction',
        ] + $parameters);

        if (!$this->viewMatched($view)) {
            throw new LogicException("Couldn't match view '{$viewType}' for Content #{$content->id}");
        }

        $controllerReference = $view->getControllerReference();

        if ($controllerReference === null || $controllerReference->controller === 'ng_content:viewAction') {
            return $this->viewRenderer->render($view);
        }

        return $this->renderController($view, $controllerReference, ['layout' => $layout] + $parameters);
    }

    /**
     * This is the only way to check if the view actually matched?
     *
     * @param \Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView $contentView
     *
     * @return bool
     */
    private function viewMatched(ContentView $contentView): bool
    {
        return !empty($contentView->getConfigHash());
    }

    private function getContent(ValueObject $contentOrLocation): ValueObject
    {
        if ($contentOrLocation instanceof Content || $contentOrLocation instanceof APIContent) {
            return $contentOrLocation;
        }

        if ($contentOrLocation instanceof Location || $contentOrLocation instanceof APILocation) {
            // eZ location also has a lazy loaded "content" property
            return $contentOrLocation->content;
        }

        throw new LogicException('Given value must be Content or Location instance.');
    }

    private function getLocation(ValueObject $contentOrLocation): ?ValueObject
    {
        if ($contentOrLocation instanceof Location || $contentOrLocation instanceof APILocation) {
            return $contentOrLocation;
        }

        if ($contentOrLocation instanceof Content) {
            return $contentOrLocation->mainLocation;
        }

        if ($contentOrLocation instanceof APIContent) {
            // View builder will not load the main location if it is not provided,
            // this makes sure it is available to the template
            try {
                return $this->locationService->loadLocation($contentOrLocation->contentInfo->mainLocationId);
            } catch (NotFoundException $e) {
                return null;
            }
        }

        throw new LogicException('Given value must be Content or Location instance.');
    }

    private function renderController(ContentView $contentView, ControllerReference $controllerReference, array $arguments): string
    {
        $controller = $this->resolveController($controllerReference);
        $arguments = $this->resolveControllerArguments($contentView, $controller, $arguments);

        $result = \call_user_func_array($controller, $arguments);

        if ($result instanceof ContentView) {
            return $this->viewRenderer->render($result);
        }

        if ($result instanceof Response) {
            return $result->getContent();
        }

        throw new LogicException('Controller result must be ContentView or Response instance');
    }

    private function resolveController(ControllerReference $controllerReference): callable
    {
        $controllerRequest = new Request();
        $controllerRequest->attributes->set('_controller', $controllerReference->controller);
        $controller = $this->controllerResolver->getController($controllerRequest);

        if ($controller === false) {
            throw new NotFoundHttpException(
                \sprintf('Unable to find the controller "%s".', $controllerReference->controller)
            );
        }

        return $controller;
    }

    private function resolveControllerArguments(ContentView $contentView, callable $controller, array $arguments): array
    {
        $request = $this->requestStack->getMasterRequest();

        if ($request === null) {
            throw new LogicException('A Request must be available.');
        }

        $request = $request->duplicate();
        $request->attributes->set('view', $contentView);
        $request->attributes->add($contentView->getParameters());
        $request->attributes->add($arguments);

        return $this->argumentResolver->getArguments($request, $controller);
    }
}
