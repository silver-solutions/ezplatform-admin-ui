<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Commerce\Bundle\AdminUiBundle\Menu\Builder;

use EzSystems\EzPlatformAdminUi\Menu\MenuItemFactory;
use EzSystems\EzPlatformAdminUi\Menu\AbstractBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use InvalidArgumentException;
use Ibexa\Commerce\Bundle\AdminUiBundle\Menu\Event\EshopConfigureMenuEventName;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * KnpMenuBundle Menu Builder service implementation.
 *
 * @see https://symfony.com/doc/current/bundles/KnpMenuBundle/menu_builder_service.html
 */
class ConfigurationSettingsBuilder extends AbstractBuilder implements TranslationContainerInterface
{
    /* Menu items */
    const ITEM__SAVE = 'ezcommerce_shop__configuration_settings__save';

    /** @var \Symfony\Contracts\Translation\TranslatorInterface */
    private $translator;

    public function __construct(
        MenuItemFactory $factory,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator
    ) {
        parent::__construct($factory, $eventDispatcher);

        $this->translator = $translator;
    }

    /**
     * @param array $options
     *
     * @return ItemInterface
     *
     * @throws InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function createStructure(array $options): ItemInterface
    {
        /** @var ItemInterface|ItemInterface[] $menu */
        $menu = $this->factory->createItem('root');
        $menuItems = [];

        $menuItems[self::ITEM__SAVE] = $this->createMenuItem(
            self::ITEM__SAVE,
            [
                'attributes' => [
                    'class' => 'btn--trigger',
                    'data-click' => '#ez-save-configuration',
                    /** @Desc("Save") */
                    'title' => $this->translator->trans(self::ITEM__SAVE, [], 'menu'),
                ],
                'extras' => [
                    'icon' => 'save',
                    'orderNumber' => 10,
                ],
            ]
        );

        $menu->setChildren($menuItems);

        return $menu;
    }

    /**
     * @return string
     */
    protected function getConfigureEventName(): string
    {
        return EshopConfigureMenuEventName::ESHOP_CONFIGURATION_SETTINGS_ACTIONS;
    }

    /**
     * @return Message[]
     */
    public static function getTranslationMessages(): array
    {
        return [
            (new Message(self::ITEM__SAVE, 'menu'))->setDesc('Save'),
        ];
    }
}
