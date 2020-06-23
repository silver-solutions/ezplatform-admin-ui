<?php
/**
 * Product eZ Commerce
 *
 * A powerful e-commerce solution for online shops with a focus on content & commerce.
 *
 * http://www.silversolutions.de/en/e-Commerce/silver.eShop-The-B2B-Shop-Software
 *
 * This file contains the class MenuAdminController
 *
 * @copyright Copyright (C) 2019 silver.solutions GmbH. All rights reserved.
 * @license BUL - For full copyright and license information view LICENSE file distributed with this source code.
 * @version v2.5.0
 * @package eZ Commerce
 */

namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Siso\ShopPriceEnginePluginBundle\Service\ProductManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Siso\ShopPriceEnginePluginBundle\Entity\ShippingCost;

class MenuAdminController extends AbstractController
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Siso\ShopPriceEnginePluginBundle\Service\ProductManager */
    private $productManager;

    /** @var array */
    private $checkoutValues;

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
    private $registry;

    public function __construct(
        ConfigResolverInterface $configResolver,
        ProductManager $productManager,
        Registry $registry,
        array $checkoutValues
    ) {
        $this->configResolver = $configResolver;
        $this->productManager = $productManager;
        $this->registry = $registry;
        $this->checkoutValues = $checkoutValues;
    }

    /**
     * Main Price/Stock Controller
     * @return Response
     */
    public function priceStockManagementAction()
    {

        $response = new Response();
        return $this->render(
            '@ezdesign/price_stock_management.html.twig',
            array(),
            $response
        );
    }

    /**
     * Gets prices for a sku and variants and returns a Json array
     *
     * @param string $sku
     * @param string|null $shopId
     * @param $currency
     * @return JsonResponse
     */
    public function fetchPricesAction(Request $request, $shopId = null, $currency = null)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_prices'))){
            throw new AccessDeniedException();
        }
        $shopId = $shopId ?: 'main';

        $sku = $request->request->get('sku','');


        $response = new JsonResponse();
        $customerGroupSettings = $this->configResolver->getParameter('sesselection.ses_customer_group','siso_core');
        //$customerGroupSettings['options'];

        $productPrices = $this->productManager->getPrices($sku, $shopId, $currency, $this->getCustomerGroups($customerGroupSettings['options']));

        if (!$productPrices) {
            return $this->getMessageResponse($response,'message.product_not_found');
        }

        $result = array(
            'status' => 1,
            'shopId' => $shopId,
            'name' => $productPrices['productNode']->name,
            'sku' => $productPrices['productNode']->sku,
            'baseprice' => $productPrices['productNode']->price->price->price,
            'prices' => $productPrices['priceResults'],
            'variants' => $productPrices['variantAttributes'],
            'customerGroups' => $this->getCustomerGroups($customerGroupSettings['options']),
            'currency' => $currency,
            'currencyList' => $this->configResolver->getParameter('currency_list', 'siso_core')
        );

        $response->setData(
            array(
                'result' => $result,
            )
        );

        return $response;
    }

    protected function getCustomerGroups($groups)
    {
        $uiGroups = array();
        foreach ($groups as $key => $label) {
            $uiGroups[] = array('groupId' => $key, 'label' => $label);
        }
        return $uiGroups;
    }

    /**
     * @param Request $request
     * @param string|null $shopId
     * @throws \Exception
     */
    public function updatePricesAction(Request $request, $shopId = null)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_prices'))){
            throw new AccessDeniedException();
        }

        $priceSet = $request->request->get('prices', array());
        $shopId = $shopId ? $shopId: 'main';

        return new JsonResponse($this->productManager->updatePrices($priceSet, $shopId));
    }

    /**
     * Fetches stock for a sku inc. variants
     *
     * @param Request $request
     * @param null $shopId
     * @return JsonResponse
     ** @throws \Exception
     */
    public function fetchStockAction(Request $request, $shopId = null)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_stock'))){
            throw new AccessDeniedException();
        }
        $shopId = $shopId ?: 'main';
        $response = new JsonResponse();
        $sku = $request->request->get('sku','');

        $productStock = $this->productManager->getStock($sku, $shopId);

        if (!$productStock) {
            return $this->getMessageResponse($response,'message.product_not_found');
        }

        return new JsonResponse(array
        (
            'sku'  => $sku,
            'status' => 1,
            'stock' => $productStock
        ));
    }

    /**
     * Stores stock in the DB
     *
     * @param Request $request
     * @param null $shopId
     * @return JsonResponse
     *
     */
    public function updateStockAction(Request $request, $shopId = null)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_stock'))){
            throw new AccessDeniedException();
        }
        $stockUpdate = $request->request->get('stock', array());
        $shopId = $shopId ? $shopId: 'main';

        $result = $this->productManager->updateStock($stockUpdate, $shopId);

        return new JsonResponse(array
        (
            'sku'  => $stockUpdate['sku'],
            'status' => $result['status'],

        ));
    }

    /**
     * Gets shipping for a sku and variants and returns a Json array
     *
     * @return JsonResponse
     ** @throws \Exception
     */
    public function fetchShippingCostsAction()
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_shipping_costs'))){
            throw new AccessDeniedException();
        }

        $shippingMethods = $this->checkoutValues['shippingMethods'];
        $shippingMethodsResponse = array();

        foreach ($shippingMethods as $key => $shippingMethod) {
            $shippingMethodsResponse[] = array(
                'label' => $shippingMethod,
                'value' => $key
            );
        }

        $repository = $this->registry->getRepository('ShopPriceEnginePluginBundle:ShippingCost');
        $shippingCostsList = $repository->findAll();

        $shippingCostsListArray = array();
        foreach($shippingCostsList as $key => $shippingCost){
            $country = $shippingCost->getCountry();
            $country = array('value' => $country, 'label' => $country);

            $shopId = $shippingCost->getShopId();
            $shopId = array('value' => $shopId, 'label' => $shopId);

            $shippingMethod = array('value' => $shippingCost->getShippingMethod(),
                'label' => $shippingCost->getShippingMethod());

            $shippingCostsListArray[$key] = array(
                'id' => $shippingCost->getId(),
                'country' => $country,
                'state' => $shippingCost->getState(),
                'zip' => $shippingCost->getZip(),
                'shopId' => $shopId,
                'shippingMethod' => $shippingMethod,
                'shippingCost' => $shippingCost->getShippingCost(),
                'currency' => $shippingCost->getCurrency(),
                'valueOfGoods' => $shippingCost->getValueOfGoods()
            );
        }

        $response = new JsonResponse();
        $response->setData(array(
            'shippingCostsList' => $shippingCostsListArray,
            'shippingMethods' => $shippingMethodsResponse
        ));
        return $response;
    }


    /**
     * Stores shipping costs in the DB
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function updateShippingCostsAction(Request $request)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_shipping_costs'))){
            throw new AccessDeniedException();
        }
        /** ShippingCostRepository $repository */
        $repository = $this->registry->getRepository('ShopPriceEnginePluginBundle:ShippingCost');
        $shippingCostsList = $request->request->get('shippingCostsList', array());
        $shippingCostObjects = array();

        foreach($shippingCostsList as $shippingCostLine){
            $shippingCost = new ShippingCost();
            $shippingCost->setShopId($shippingCostLine['shopId']['value']);
            $shippingCost->setCountry($shippingCostLine['country']['label']);
            $shippingCost->setState($shippingCostLine['state']);
            $shippingCost->setZip($shippingCostLine['zip']);
            $shippingCost->setShippingMethod($shippingCostLine['shippingMethod']['value']);
            $shippingCost->setShippingCost($shippingCostLine['shippingCost']);
            $shippingCost->setCurrency($shippingCostLine['currency']);
            $shippingCost->setValueOfGoods($shippingCostLine['valueOfGoods']);

            $shippingCostObjects[] = $shippingCost;
        }

        $response = $repository->setShippingCosts($shippingCostObjects);
        return new JsonResponse($response);
    }

    /**
     * Sets proper json Response
     *
     * @param JsonResponse $response
     * @param string $message
     * @param int $status
     * @return JsonResponse
     *
     */
    protected function getMessageResponse(JsonResponse $response, $message, $status = 0)
    {
        $result = array();
        $result['message'] = $message;
        $result['status'] = $status;
        $response->setData(
            array(
                'result' => $result,
            )
        );
        return $response;
    }
}
