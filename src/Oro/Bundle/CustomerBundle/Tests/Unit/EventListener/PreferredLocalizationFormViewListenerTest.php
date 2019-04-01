<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\CustomerBundle\EventListener\PreferredLocalizationFormViewListener;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Form\FormView;

class PreferredLocalizationFormViewListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    /**
     * @var PreferredLocalizationFormViewListener
     */
    private $listener;

    protected function setUp()
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->listener = new PreferredLocalizationFormViewListener($this->websiteManager);
    }

    public function testOnEntityEdit()
    {
        $template = '<div>Some template</div>';
        $formView = new FormView();
        /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject $env */
        $env = $this->createMock(\Twig_Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroCustomerBundle::CustomerUser/widget/preferredLocalizationForm.html.twig',
                ['form' => $formView]
            )
            ->willReturn($template);
        $scrollData = new ScrollData(['dataBlocks' => [0 => ['subblocks' => [0 => []]]]]);
        $event = new BeforeListRenderEvent($env, $scrollData, new \stdClass(), $formView);

        $this->listener->onEntityEdit($event);
        $expectedScrollData = [
            'dataBlocks' => [
                0 => [
                    'subblocks' => [
                        0 => [
                            'data' => [
                                $template,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedScrollData, $scrollData->getData());
    }

    public function testOnEntityViewWhenNoCustomerUserSettings()
    {
        $this->websiteManager->expects($this->once())
            ->method('getDefaultWebsite')
            ->willReturn(new Website());
        /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject $env */
        $env = $this->createMock(\Twig_Environment::class);
        $env->expects($this->never())
            ->method('render');
        $scrollData = new ScrollData(['dataBlocks' => [0 => ['subblocks' => [0 => []]]]]);
        $event = new BeforeListRenderEvent($env, $scrollData, new CustomerUser());

        $this->listener->onEntityView($event);
        self::assertEquals(['dataBlocks' => [0 => ['subblocks' => [0 => []]]]], $scrollData->getData());
    }

    public function testOnEntityViewWhenNoLocalizationInCustomerUserSettings()
    {
        $defaultWebsite = new Website();
        $customerUser = new CustomerUser();
        $customerUser->setWebsiteSettings(new CustomerUserSettings($defaultWebsite));
        $this->websiteManager->expects($this->once())
            ->method('getDefaultWebsite')
            ->willReturn($defaultWebsite);
        /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject $env */
        $env = $this->createMock(\Twig_Environment::class);
        $env->expects($this->never())
            ->method('render');
        $scrollData = new ScrollData(['dataBlocks' => [0 => ['subblocks' => [0 => []]]]]);
        $event = new BeforeListRenderEvent($env, $scrollData, $customerUser);

        $this->listener->onEntityView($event);
        self::assertEquals(['dataBlocks' => [0 => ['subblocks' => [0 => []]]]], $scrollData->getData());
    }

    public function testOnEntityView()
    {
        $localization = new Localization();
        $defaultWebsite = new Website();
        $customerUser = new CustomerUser();
        $customerUser->setWebsiteSettings(
            (new CustomerUserSettings($defaultWebsite))->setLocalization($localization)
        );
        $this->websiteManager->expects($this->once())
            ->method('getDefaultWebsite')
            ->willReturn($defaultWebsite);
        $template = '<div>Some template</div>';
        /** @var \Twig_Environment|\PHPUnit\Framework\MockObject\MockObject $env */
        $env = $this->createMock(\Twig_Environment::class);
        $env->expects($this->once())
            ->method('render')
            ->with(
                'OroCustomerBundle::CustomerUser/widget/preferredLocalizationView.html.twig',
                ['preferredLocalization' => $localization]
            )
            ->willReturn($template);
        $scrollData = new ScrollData(['dataBlocks' => [0 => ['subblocks' => [0 => []]]]]);
        $event = new BeforeListRenderEvent($env, $scrollData, $customerUser);

        $this->listener->onEntityView($event);
        $expectedScrollData = [
            'dataBlocks' => [
                0 => [
                    'subblocks' => [
                        0 => [
                            'data' => [
                                $template,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedScrollData, $scrollData->getData());
    }
}
