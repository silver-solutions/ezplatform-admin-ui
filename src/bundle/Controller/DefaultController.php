<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use EzSystems\EzPlatformAdminUi\Notification\NotificationHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Siso\ShopPriceEnginePluginBundle\Service\CsvImportExportHandler;
use Silversolutions\Bundle\TranslationBundle\Services\TransService;

/**
 * Class DefaultController
 */
class DefaultController extends AbstractController
{
    /** @var CsvImportExportHandler */
    protected $csvImportExportHandler;

    /** @var TransService */
    protected $transService;

    /** @var \EzSystems\EzPlatformAdminUi\Notification\NotificationHandlerInterface */
    private $notificationHandler;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    public function __construct(
        CsvImportExportHandler $csvImportExportHandler,
        TransService $transService,
        NotificationHandlerInterface $notificationHandler,
        ConfigResolverInterface $configResolver
    ) {
        $this->csvImportExportHandler = $csvImportExportHandler;
        $this->transService = $transService;
        $this->notificationHandler = $notificationHandler;
        $this->configResolver = $configResolver;
    }

    /**
     * Returns csv file with all products with prices
     *
     * @param Request $request
     * @param string $shopId
     * @return Response
     */
    public function priceDownloadAction(Request $request, $shopId)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_prices'))){
            throw new AccessDeniedException();
        }
        try {
            $shopId = $this->checkShopId($shopId);

            $response = new StreamedResponse();

            $response->setCallback(function() use ($shopId) {
                $this->csvImportExportHandler->exportPricesCsv($shopId);
            });

            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set('Content-Disposition', 'attachment; filename="price_export.csv"');

            return $response;
        } catch (\Exception $e) {

            return new RedirectResponse($this->getReferer($request) . '?message=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Upload csv with new shop prices
     *
     * @param Request $request
     * @param string $shopId
     * @return Response
     */
    public function priceUploadAction(Request $request, $shopId)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_prices'))){
            throw new AccessDeniedException();
        }

        $shopId = $this->checkShopId($shopId);

        try {
            $file = $request->files->get('file');
            $this->checkFileExists($file);

            $importedSKUs = $this->csvImportExportHandler->importPricesCsv(
                $file->getRealPath(),
                $shopId
            );

            $message = $this->transService->translate(
                'msg.price_upload_success',
                null,
                array('%products%' => $importedSKUs['count'])
            );
            $this->sendNotification('success', $message);
        } catch(\Exception $e) {
            $message = $e->getMessage();
            $this->sendNotification('error', $message);
        }

        return new RedirectResponse($this->getReferer($request));
    }


    protected function sendNotification($mode, $message)
    {
        $this->notificationHandler->$mode($message);
    }
    /**
     * Returns csv file with all products with stock
     *
     * @param Request $request
     * @param string $shopId
     * @return Response
     */
    public function stockDownloadAction(Request $request, $shopId)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_stock'))){
            throw new AccessDeniedException();
        }
        try {
            $shopId = $this->checkShopId($shopId);

            $response = new StreamedResponse();

            $response->setCallback(function() use ($shopId) {
                $this->csvImportExportHandler->exportStocksCsv($shopId);
            });

            $response->headers->set('Content-Type', 'application/force-download');
            $response->headers->set('Content-Disposition', 'attachment; filename="stock_export.csv"');

            return $response;
        } catch (\Exception $e) {
            $this->sendNotification('error', $e->getMessage());
            return new RedirectResponse($this->getReferer($request));
        }
    }

    /**
     * Upload csv with new shop stock
     * @param Request $request
     * @param string $shopId
     * @return Response
     */
    public function stockUploadAction(Request $request, $shopId)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_policy', 'manage_stock'))){
            throw new AccessDeniedException();
        }

        $shopId = $this->checkShopId($shopId);

        try {
            $file = $request->files->get('file');
            $this->checkFileExists($file);

            $importedSKUs = $this->csvImportExportHandler->importStocksCsv(
                $file->getRealPath(),
                $shopId
            );

            $message = $this->transService->translate(
                'msg.stock_upload_success',
                null,
                array('%products%' => $importedSKUs['count'])
            );
            $this->sendNotification('success', $message);
        } catch(\Exception $e) {
            $message = $e->getMessage();
            $this->sendNotification('error', $message);
        }

        return new RedirectResponse($this->getReferer($request) );
    }

    /**
     * Check if the shop id exists
     *
     * @param string $shopId
     * @throws \InvalidArgumentException
     * @return string
     */
    private function checkShopId($shopId)
    {
        $shopList = $this->configResolver->getParameter('shop_list', 'siso_price');
        foreach ($shopList as $shop) {
            if ($shop === $shopId) {
                return $shopId;
            }
        }

        $message = $this->transService->translate('msg.invalid_shop_id', null, array('%shopId%' => $shopId));
        throw new \InvalidArgumentException($message);
    }

    /**
     * Check if uploaded file exists
     *
     * @param mixed $file
     * @throws \InvalidArgumentException
     */
    private function checkFileExists($file)
    {
        if (!$file instanceof UploadedFile) {
            $message = $this->transService->translate('msg.invalid_csv_file', null, array());
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * Returns referer string
     *
     * @param Request $request
     * @return string
     */
    private function getReferer(Request $request)
    {
        return strtok($request->headers->get('referer'),'?');
    }
}
