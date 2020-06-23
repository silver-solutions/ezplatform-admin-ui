<?php
/**
 * Product eZ Commerce
 *
 * A powerful e-commerce solution for online shops with a focus on content & commerce.
 *
 * http://www.silversolutions.de/en/e-Commerce/silver.eShop-The-B2B-Shop-Software
 *
 * This file contains the class ShowLogController
 *
 * @copyright Copyright (C) 2019 silver.solutions GmbH. All rights reserved.
 * @license BUL - For full copyright and license information view LICENSE file distributed with this source code.
 * @version v2.5.0
 * @package eZ Commerce
 */

namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use Siso\AdminErpPluginBundle\Service\QueryLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Yaml\Yaml;

/**
 *
 *
 * Class ShowLogController
 */
class ShowLogController extends AbstractController
{
    /** @var \Siso\AdminErpPluginBundle\Service\QueryLogService */
    private $queryLogService;

    public function __construct(
        QueryLogService $queryLogService
    ) {
        $this->queryLogService = $queryLogService;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxShowLogMessageAction(Request $request)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_erp_logs'))){
            throw new AccessDeniedException();
        }
        $idMess = $request->get('id');
        $xml_data = $this->queryLogService->queryXML($idMess);

        return new Response($this->serial2Json($xml_data[0]['logMessage']));
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function serial2Json($data)
    {
        //recalculates items length
        $str_correct = preg_replace_callback('#s:(\d+):"(.*?)";#', function($m) { return 's:' . strlen($m[2]) . ':"' . $m[2] . '";'; }, $data);

        return json_encode(unserialize($str_correct));
    }
}
