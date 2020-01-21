<?php

namespace Oro\Bundle\FrontendBundle\Controller;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Bundle\UIBundle\Controller\ExceptionController as BaseExceptionController;
use Oro\Component\Layout\LayoutContext;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles rendering error pages for front store.
 */
class ExceptionController extends BaseExceptionController
{
    const EXCEPTION_ROUTE_NAME = 'oro_frontend_exception';

    /** @var ContainerInterface */
    private $container;

    /** @var bool */
    private $showException;

    /**
     * @param ContainerInterface $container
     * @param bool $showException
     */
    public function __construct(ContainerInterface $container, $showException)
    {
        $this->container = $container;
        $this->showException = $showException;

        parent::__construct($container, $showException);
    }

    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, $exception, DebugLoggerInterface $logger = null)
    {
        if ($this->isLayoutRendering($request)) {
            $this->updateRequest($request);

            $code = $this->getStatusCode($exception);
            $text = $this->getStatusText($code);

            $context = new LayoutContext(['data' => ['status_code' => $code , 'status_text' => $text]]);
            $context->set('route_name', self::EXCEPTION_ROUTE_NAME);

            try {
                $layout = $this->container->get(LayoutManager::class)
                    ->getLayout($context);

                return new Response($layout->render());
            } catch (\Throwable $e) {
                //can't render layout template, because of errors in some layout templates
            }
        }

        return parent::showAction($request, $exception, $logger);
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isLayoutRendering(Request $request)
    {
        return
            $this->container->get(FrontendHelper::class)->isFrontendUrl($request->getPathInfo())
            && $request->getRequestFormat() === 'html'
            && !$this->showException($request)
            && !$this->isCircularHandlingException();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function showException(Request $request)
    {
        return $request->attributes->get('showException', $this->showException);
    }

    /**
     * @param int $code
     * @return string
     */
    protected function getStatusText($code)
    {
        return $this->container->get(TranslatorInterface::class)
            ->trans(sprintf('oro_frontend.exception.status_text.%d', $code));
    }

    /**
     * @return bool
     */
    private function isCircularHandlingException(): bool
    {
        $parentRequest = $this->getParentRequest();

        return $parentRequest && $parentRequest->get('_route') === self::EXCEPTION_ROUTE_NAME;
    }

    /**
     * @param Request $request
     */
    private function updateRequest(Request $request): void
    {
        $parentRequest = $this->getParentRequest();
        if (!$parentRequest) {
            return;
        }

        // emulate original request to render valid layout page
        $request->query->add($parentRequest->query->all());
        $request->request->add($parentRequest->request->all());
        $request->attributes->add($parentRequest->attributes->all());
        $request->cookies->add($parentRequest->cookies->all());
        $request->files->add($parentRequest->files->all());
        $request->server->add($parentRequest->server->all());
    }

    /**
     * @return Request|null
     */
    private function getParentRequest(): ?Request
    {
        return $this->container->get(RequestStack::class)->getParentRequest();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                FrontendHelper::class,
                LayoutManager::class,
                RequestStack::class,
                TranslatorInterface::class,
            ]
        );
    }
}
