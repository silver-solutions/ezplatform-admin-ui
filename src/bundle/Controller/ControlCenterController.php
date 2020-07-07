<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Siso\AdminErpPluginBundle\Service\QueryLogService;
use Siso\AdminErpPluginBundle\Service\QueryMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Siso\AdminErpPluginBundle\Service\ResendEmailService;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Yaml\Yaml;

class ControlCenterController extends AbstractController
{
    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Siso\AdminErpPluginBundle\Service\QueryLogService */
    private $queryLogService;

    /** @var \Siso\AdminErpPluginBundle\Service\QueryMailService */
    private $queryMailService;

    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    private $requestStack;

    public function __construct(
        ConfigResolverInterface $configResolver,
        QueryLogService $queryLogService,
        QueryMailService $queryMailService,
        RequestStack $requestStack
    ) {
        $this->configResolver = $configResolver;
        $this->queryLogService = $queryLogService;
        $this->queryMailService = $queryMailService;
        $this->requestStack = $requestStack;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function showAction(Request $request)
    {
        $parameters = $request->request->all();

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_emails'))){
            throw new AccessDeniedException();
        }

        $defaultDateStart = date('Y-m-d');
        $defaultDateEnd = date('Y-m-d');

        $userId = (isset($parameters['ez-email-archive-user-id']))?$parameters['ez-email-archive-user-id']:'';
        $strDateTimeStart = (isset($parameters['ez-email-archive-date-start']))?$parameters['ez-email-archive-date-start'] :$defaultDateStart;
        $strDateTimeEnd = (isset($parameters['ez-email-archive-date-end']))?$parameters['ez-email-archive-date-end'] :$defaultDateEnd;
        $strDateTimeEnd .= ' 23:59:59';

        $textSearch = (isset($parameters['ez-email-archive-text-search']))?$parameters['ez-email-archive-text-search']:'';

        $dateTimeStart = new \DateTime($strDateTimeStart);
        $dateTimeEnd = new \DateTime($strDateTimeEnd);

        $emails = $this->queryMailService->query($dateTimeStart, $dateTimeEnd, $userId, $textSearch);

        $erpConnection = $this->configResolver->getParameter('erp_connection', 'siso_core');

        return $this->render('@ezdesign/control_center.html.twig',
            array('emails'     => $emails,
                  'parameters' => $parameters,
                  'erp_connection' => $erpConnection,
            ));
    }

    /**
     * Display ERP overview
     *
     * @return Response
     */
    public function showOverviewAction()
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_erp'))){
            throw new AccessDeniedException();
        }
        $webConnectorServiceLocation = $this->configResolver->getParameter('web_connector.service_location', 'siso_erp');

        $messageMappingData = null;

        // @Todo: What is this and how to deal with it
        $config = Yaml::parse(file_get_contents( __DIR__ . '/../../../../ezcommerce-shop/src/Silversolutions/Bundle/EshopBundle/Resources/config/messages.yml' ));

        foreach($config['parameters'] as $key => $values){
            if ("siso_erp.default.message_settings." == substr($key,0,34)) {
                $paramNameAndScope = explode( '.default.', $key );
                $configuration = $this->configResolver->getParameter($paramNameAndScope[1], $paramNameAndScope[0]);
                if (!empty($configuration && array_key_exists('mapping_identifier', $configuration))) {

                    $mappingPath = __DIR__ .'/../../../../ezcommerce-shop/src/Silversolutions/Bundle/EshopBundle/Resources/mapping/wc3-nav/xsl/';
                    $requestMappingPath = $mappingPath . 'request.' . $configuration['mapping_identifier'] . '.xsl';
                    $responseMappingPath = $mappingPath . 'response.' . $configuration['mapping_identifier'] . '.xsl';

                    $messageMappingData[$key]['mapping_identifier'] = $configuration['mapping_identifier'];
                    $messageMappingData[$key]['request_mapping'] = null;
                    $messageMappingData[$key]['response_mapping'] = null;

                    if (file_exists($requestMappingPath)) {
                        $requestMapping = file_get_contents($requestMappingPath, true);
                        $messageMappingData[$key]['request_mapping']['file_content'] = $requestMapping;
                        $messageMappingData[$key]['request_mapping']['file_path'] = $requestMappingPath;
                    }
                    if (file_exists($responseMappingPath)) {
                        $responseMapping = file_get_contents($responseMappingPath, true);
                        $messageMappingData[$key]['response_mapping']['file_content'] = $responseMapping;
                        $messageMappingData[$key]['response_mapping']['file_path'] = $responseMappingPath;
                    }
                }
            }
        }

        return $this->render(
            '@ezdesign/part/erp_overview.html.twig',
            array(
                'web_onnector_service_location' => $webConnectorServiceLocation,
                'message_mapping_data' => $messageMappingData
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function showEventsAction($request=null)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_erp_logs'))){
            throw new AccessDeniedException();
        }
        if (!isset($request)){
            $request = $this->requestStack->getCurrentRequest();
            $parameters = $request->request->all();
        } else {
            $parameters = $request->all();
        }

        $userId = (isset($parameters['userId']))?$parameters['userId']:'';
        $strDateTimeStart = (isset($parameters['ez-erp-logs-start-date']))?$parameters['ez-erp-logs-start-date']:'';
        $strDateTimeEnd = (isset($parameters['ez-erp-logs-end-date']))?$parameters['ez-erp-logs-end-date']:'';
        $textSearch = (isset($parameters['ez-erp-logs-text']))?$parameters['ez-erp-logs-text']:'';
        $requestId = (isset($parameters['ez-erp-logs-request-id']))?$parameters['ez-erp-logs-request-id']:'';
        $measuringPoint = (isset($parameters['ez-erp-logs-measuring-point']))?$parameters['ez-erp-logs-measuring-point']:'';
        $offset = 0;
        $limit = 20;

        $events = $this->queryLogDB(
            $strDateTimeStart,
            $strDateTimeEnd,
            $userId,
            $textSearch,
            $measuringPoint,
            $requestId,
            $limit,
            $offset
        );

        $chartData = $this->extractChartData($events);
        $dataDateTime = $this->extractDateTime($chartData);
        $dataColumns = $this->extractColumns($chartData);

        return $this->render('@ezdesign/part/erp_logs.html.twig',
            array(
                'events'     => $events,
                'measuringPoints'  => array(
                    '120_complete',
                    '150_mapping',
                    '180_soap',
                    '220_soap',
                    '250_mapping',
                    '280_complete',
                ),
                'parameters' => $parameters,
                'date_time'  => $dataDateTime,
                'columns'    => $dataColumns
            )
        );
    }

    /**
     * @param ParameterBag $request
     *
     * @return Response
     */
    public function showGraphAction($request)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_erp_logs'))){
            throw new AccessDeniedException();
        }
        $parameters = $request->all();

        $userId = (isset($parameters['ez-erp-performance-user-id']))?$parameters['ez-erp-performance-user-id']:'';
        $strDateTimeStart = (isset($parameters['ez-erp-performance-date-start']))?$parameters['ez-erp-performance-date-start']:'';
        $strDateTimeEnd = (isset($parameters['ez-erp-performance-date-end']))?$parameters['ez-erp-performance-date-end']:'';
        $searchtext = (isset($parameters['ez-erp-performance-text']))?$parameters['ez-erp-performance-text']:'';

        $events = $this->queryLogDB($strDateTimeStart, $strDateTimeEnd, $userId, $searchtext,'','', 1000);

        $chartData = $this->extractChartData($events);
        $dataDateTime = $this->extractDateTime($chartData);
        $dataColumns = $this->extractColumns($chartData);

        return $this->render('@ezdesign/part/erp_graph.html.twig',
            array(
                'parameters' => $parameters,
                'date_time'  => $dataDateTime,
                'columns'    => $dataColumns
            ));

    }

    private function queryLogDB(
        $strDateTimeStart,
        $strDateTimeEnd,
        $userId,
        $textSearch,
        $measuringPoint = '',
        $requestId = '',
        $limit = '',
        $offset = ''
    ) {
        $dateTimeStart = new \DateTime($strDateTimeStart);
        $dateTimeEnd = new \DateTime($strDateTimeEnd);

        return $this->queryLogService->query(
            $dateTimeStart,
            $dateTimeEnd,
            $userId,
            $textSearch,
            $measuringPoint,
            $requestId,
            $limit,
            $offset
        );
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function extractColumns($data)
    {
        $columns = array();
        foreach($data as $column)
        {
            if($column[0] == 'DateTime')
            {
                continue;
            }

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function extractDateTime($data)
    {
        $date_time = $data['DateTime'];
        array_shift($date_time);

        return $date_time;
    }

    /**
     * @param $column
     * @param $data
     *
     * @return bool
     */
    private function columnExists($column, $data)
    {
        foreach($data as $value)
        {
            if($value[0] === $column)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $events
     *
     * @return array
     */
    private function extractChartData($events)
    {
        $chartData = array('DateTime' => array('DateTime'));
        $numberElements = 0;
        $numberColumn = 0;

        foreach($events as $event)
        {
            if($event['messageIdentifier'] !== false)
            {
                //Extracts the names of the column
                if(!$this->columnExists($event['messageIdentifier'], $chartData))
                {
                    $chartData[$event['messageIdentifier']][0] = $event['messageIdentifier'];

                    //Fills with 0s to reach the length of the other columns
                    if($numberElements > 0)
                    {
                        for($i = 0; $i < $numberElements; $i++)
                        {
                            $chartData[$event['messageIdentifier']][] = 0;
                        }
                    }
                    $numberColumn++;
                }

                //Extracts processing time, if present, and the date/time
                if($event['processingTime'] !== null)
                {
                    $chartData[$event['messageIdentifier']][] = $event['processingTime'];

                    //Set processing time to 0 to all other columns
                    $columnNames = array_keys($chartData);
                    foreach($columnNames as $columnName)
                    {
                        if($columnName !== $event['messageIdentifier'] && $columnName !== 'DateTime')
                        {
                            $chartData[$columnName][] = 0;
                        }
                    }

                    //Extracts date and time
                    $chartData['DateTime'][] = $event['logTimestamp']->format('Y-m-d H:i:s');

                    //Increase the number of total elements
                    $numberElements++;
                }
            }
        }

        return $chartData;
    }
}
