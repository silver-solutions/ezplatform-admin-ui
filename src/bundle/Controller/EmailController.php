<?php
/**
 * Product eZ Commerce
 *
 * A powerful e-commerce solution for online shops with a focus on content & commerce.
 *
 * http://www.silversolutions.de/en/e-Commerce/silver.eShop-The-B2B-Shop-Software
 *
 * This file contains the class EcommerceEmailController
 *
 * @copyright Copyright (C) 2019 silver.solutions GmbH. All rights reserved.
 * @license BUL - For full copyright and license information view LICENSE file distributed with this source code.
 * @version v2.5.0
 * @package eZ Commerce
 */

namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use Siso\AdminErpPluginBundle\Service\QueryMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Siso\AdminErpPluginBundle\Service\ResendEmailService;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class EmailController extends AbstractController
{
    /** @var \Siso\AdminErpPluginBundle\Service\QueryMailService */
    private $queryMailService;

    /** @var \Siso\AdminErpPluginBundle\Service\ResendEmailService */
    private $resendEmailService;

    public function __construct(
        QueryMailService $queryMailService,
        ResendEmailService $resendEmailService
    ) {
        $this->queryMailService = $queryMailService;
        $this->resendEmailService = $resendEmailService;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxShowMailContentAction(Request $request)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_emails'))){
            throw new AccessDeniedException();
        }
        $idMess = $request->get('id');
        $message = $this->queryMailService->queryMailContent($idMess);

        $response = $message[0]['logMessage'];
        return new Response($response);
    }

    /**
     * resends the email by given id
     *
     * @param Request $request
     * @return Response
     *
     */
    public function ajaxResendEmailAction(Request $request)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_emails'))){
            throw new AccessDeniedException();
        }
        $id = $request->query->get('id');

        $email = $this->queryMailService->queryEmailById($id);
        $mailContent = $email[0]['logMessage'];
        $sender = $email[0]['sender'];
        //TODO unserialize???
        $recipient = $email[0]['receiver'];
        $subject = $email[0]['subject'];

        if (strpos($mailContent, '<html>') !== false) {
            //is html email
            $emailSent = $this->resendEmailService->sendMail($sender, $recipient, $subject, $mailContent);
        } else {
            $emailSent = $this->resendEmailService->sendMail($sender, $recipient, $subject, null, $mailContent);
        }

        $msg = 'Your E-mail was successfully resend';
        if ($emailSent !== true) {
            $msg = $emailSent;
        }

        return new Response($msg);
    }
}
