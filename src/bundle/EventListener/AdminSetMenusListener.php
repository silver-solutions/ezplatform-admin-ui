<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
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
