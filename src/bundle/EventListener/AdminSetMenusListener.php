<?php
/**
 * Product eZ Commerce
 *
 * A powerful e-commerce solution for online shops with a focus on content & commerce.
 * 
 * http://www.silversolutions.de/en/e-Commerce/silver.eShop-The-B2B-Shop-Software
 *
 * This file contains the class AdminRateReviewController
 *
 * @copyright Copyright (C) 2019 silver.solutions GmbH. All rights reserved.
 * @license BUL - For full copyright and license information view LICENSE file distributed with this source code.
 * @package eZ Commerce
 */

namespace Ibexa\Commerce\Bundle\AdminUiBundle\EventListener;

use EzSystems\EzPlatformAdminUi\Menu\Event\ConfigureMenuEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\API\Repository\Repository;
use Silversolutions\Bundle\TranslationBundle\Services\TransService;

class AdminSetMenusListener implements EventSubscriberInterface
{
    protected $authorizationChecker;


    /**
     * eZ Publish API repository instance
     *
     * @var Repository
     */
    protected $ezpublishApiRepository;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    /**
     * Dependency to the translation service. It is used for the messages.
     *
     * @var TransService
     */
    protected $transService;


    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigResolverInterface $configResolver,
        Repository $ezpublishApiRepository,
        TransService $transService
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->configResolver = $configResolver;
        $this->ezpublishApiRepository = $ezpublishApiRepository;
        $this->transService = $transService;
    }

    public static function getSubscribedEvents()
    {
        return [
            ConfigureMenuEvent::MAIN_MENU => ['onMenuConfigure', 0],
        ];
    }

    /**
     *
     *
     * @param ConfigureMenuEvent $event
     * @return void
     *
     */
    public function onMenuConfigure(ConfigureMenuEvent $event)
    {
        /** @var ConfigureMenuEvent $menu */
        $menu = $event->getMenu();

        $menu->getChild('siso_commerce')->addChild(
            'siso_control_center',
            [
                'label' =>  $this->transService->translate('Control center', null),
                'route' => 'siso_control_center'
            ]
        );
    }
}
