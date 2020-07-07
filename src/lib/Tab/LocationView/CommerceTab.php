<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Commerce\AdminUi\Tab\LocationView;

use EzSystems\EzPlatformAdminUi\Tab\AbstractTab;
use Silversolutions\Bundle\EshopBundle\Entity\BasketRepositoryInterface;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Silversolutions\Bundle\EshopBundle\Services\Catalog\CatalogDataProviderService;
use Silversolutions\Bundle\EshopBundle\Entity\Basket;

class CommerceTab extends AbstractTab
{
    /**
     * @var BasketRepositoryInterface
     */
    protected $basketService;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface $configResolver */
    protected $configResolver;

    /** @var $catalogService CatalogDataProviderService */
    protected $catalogService;

    public function getIdentifier(): string
    {
        return 'ecommerce-tab';
    }

    public function getName(): string
    {
        return /** @Desc("Custom Tab") */
            $this->translator->trans('label.price_stock_tab', []);
    }

    public function renderView(array $parameters): string
    {
        $contentTypeId = $parameters['content']->versionInfo->contentInfo->contentTypeId;
        $contentTypeIdentifier = $parameters['contentType']->identifier;
        $userId = false;
        $baskets = array();
        if ($contentTypeIdentifier == 'user') { // User
            $userId = $parameters['content']->versionInfo->contentInfo->id;

            $confirmedBaskets = $this->basketService->getAllStoredBasketsByUserId($userId,'basket','confirmed');

            $baskets = array(
                'active' => $this->basketService->getAllStoredBasketsByUserId($userId,'basket','new'),
                'confirmed' => $confirmedBaskets

            );

            $totalAmount = $this->basketService->getUserOrderAmountTotal($userId);

            return $this->twig->render('@ezdesign/tab/location_ecommerce_user_tab.html.twig', [
                'userId' => $userId,
                'basketTypes' => $baskets,
                'totalAmount' => $totalAmount,
                'chartData'  => json_encode($this->getChartData($confirmedBaskets))
            ]);
        }

        if ($contentTypeIdentifier == 'ses_product') {
            $id = $parameters['content']->versionInfo->contentInfo->mainLocationId;
            $sku = '';
            try {
                $catalogElement = $this->catalogService->getDataProvider()->fetchElementByIdentifier($id);
                if ($catalogElement) {
                    $sku = $catalogElement->sku;

                }
            } catch (\Exception $e) {
                $catalogElement = null;
            }

            return $this->twig->render('@ezdesign/tab/location_ecommerce_product_tab.html.twig', [
                'sku' => $sku,
            ]);
        }

        return "";

    }

    protected function getChartData($baskets) {

        $dates = array();
        $dates[] = 'Date';
        $amounts = array();
        $amounts[] = 'Sales (EUR)';
        foreach ($baskets as $basket) {
            /** @var Basket $basket */
            $dates[] = $basket->getDateLastModified()->format('Y-m-d');
            $amounts[]=$basket->getTotalsSumNet();
        }
        return array($dates, $amounts);
    }

    /**
     * sets basketservice
     *
     * @param BasketRepositoryInterface $basketService
     * @return void
     *
     */
    public function setBasketService(
        BasketRepositoryInterface $basketService,
        ConfigResolverInterface $configResolver,
        CatalogDataProviderService $catalogService)
    {
        $this->basketService = $basketService;
        $this->configResolver = $configResolver;
        $this->catalogService = $catalogService;
    }
}
