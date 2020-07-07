<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;

/**
 *
 *
 * Class ErpTestController
 */
class ErpTestController extends AbstractController
{
    protected $returnValueSuccess;
    protected $returnValueFailure;
    protected $ipList;
    protected $changeTableMaxInterval;
    protected $url;
    protected $username;
    protected $password;
    protected $testSku;
    protected $changeTableUpdateLogPath;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    protected $configResolver;

    public function __construct(ConfigResolverInterface $configResolver)
    {
        $this->configResolver = $configResolver;
    }


    /**
     * Checks if the URL of NAV is reachable
     *
     * @param Request $request
     * @return Response
     */
    public function ErpTestUrlAction(Request $request)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_erp'))){
            if(!$this->checkClientIp($request))
            {
                throw new AccessDeniedException();
            }
        }


        return new Response($this->testErpUrl());
    }

    /**
     * Tests if ERP url is callable
     *
     * @param $url
     * @return string
     *
     */
    protected function testErpUrl()
    {
        $this->loadConfiguration();

        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        //$response = curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);


        if($httpCode == '200')
        {
            return "OK";
        }

        return "Error - http-code: ".$httpCode;
    }

    /**
     * Loads the configuration
     */
    protected function loadConfiguration()
    {
        $returnValueSuccess = "OK";
        $returnValueFailure = "ERROR";

        $this->url = $this->configResolver->getParameter('web_connector.service_location','siso_erp');

        $this->returnValueSuccess = $returnValueSuccess;
        $this->returnValueFailure = $returnValueFailure;
        $this->ipList = $this->configResolver->getParameter('allowed_ips_erp_test','siso_control_center');

    }

    /**
     * Check if the client IP is present in the list of allowed ips
     *
     * @param Request $request
     * @return bool
     */
    protected function checkClientIp(Request $request)
    {
        $clientIp = $request->getClientIp();
        foreach($this->ipList as $allowedIp)
        {
            if(preg_match('#' . $allowedIp . '#', $clientIp))
            {
                return true;
            }
        }

        return false;
    }
}
