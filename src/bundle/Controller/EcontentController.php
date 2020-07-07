<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\View\JobFilter;

use Symfony\Component\Finder\Finder;

class EcontentController extends AbstractController
{
    private $econtentMappings = array();

    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
    private $registry;

    /** @var string */
    private $projectDir;

    public function __construct(
        Connection $connection,
        ConfigResolverInterface $configResolver,
        Registry $registry,
        string $projectDir
    ) {
        $this->connection = $connection;
        $this->configResolver = $configResolver;
        $this->registry = $registry;
        $this->projectDir = $projectDir;
    }

    /**
     * Returns infos about econtent
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function getEcontentInfoAction(Request $request)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_exports'))){
            throw new AccessDeniedException();
        }

        $sectionId = $request->query->get('sectionId');

        $doctrine = $this->connection;

        if (!$sectionId) {
            $statement = $doctrine->executeQuery('
            SELECT sve_class.class_name, count(*) as count FROM sve_object, sve_class
            WHERE sve_object.class_id = sve_class.class_id group by sve_class.class_id;
        ');
        } else {
            $statement = $doctrine->executeQuery('
            SELECT sve_class.class_name, count(*) as count FROM sve_object, sve_class
            WHERE sve_object.class_id = sve_class.class_id
                  and sve_object.section = '.$sectionId. '
            group by sve_class.class_id;
        ');
        }

        $econtentInfo = $statement->fetchAll();
        if (!$sectionId) {
            $statement = $doctrine->executeQuery('
                SELECT sve_class.class_name, count(*) as count FROM sve_object_tmp, sve_class
                WHERE sve_object_tmp.class_id = sve_class.class_id
                group by sve_class.class_id;
            ');
        } else {
            $statement = $doctrine->executeQuery('
                SELECT sve_class.class_name, count(*) as count FROM sve_object_tmp, sve_class
                WHERE sve_object_tmp.class_id = sve_class.class_id
                      and sve_object_tmp.section = '.$sectionId. '
                group by sve_class.class_id;
            ');
        }
        $econtentTmpInfo = $statement->fetchAll();

        $statement = $doctrine->executeQuery('
            SELECT class_name,sve_class_attributes.* from sve_class, sve_class_attributes
            WHERE sve_class.class_id = sve_class_attributes.class_id
            ORDER by sve_class_attributes.class_id, attribute_id
        ');
        $econtentTypesTmp = $statement->fetchAll();
        $econtentTypes = array();
        foreach ($econtentTypesTmp as $type) {
            $type['mapping'] = $this->getMappings($type['class_name'],$type['attribute_name']);
            $econtentTypes[$type['class_name']][] = $type;
        }

        return New JsonResponse(array(
            'productive' => $econtentInfo,
            'tmp'        => $econtentTmpInfo,
            'types'      => $econtentTypes
        ));
    }

    /**
     * Returns infos about mappings for econtent
     *
     * @param string $type
     * @param string $id
     * @return string
     *
     */
    protected function getMappings($type, $id)
    {
        if (!isset($this->econtentMappings[$type])) {
            $econtentMappings = $this->configResolver->getParameter('mapping.'.$type,'silver_econtent');
            $this->econtentMappings[$type] = $econtentMappings;
        }
        if (!isset($this->econtentMappings[$type])) {
            return false;
        }
        foreach ($this->econtentMappings[$type] as $key => $mapping) {
            if ($mapping['id'] == $id) {
                $mapping['attribute'] = $key;
                return $mapping;
            }
        }
        return false;
    }


    /**
     * Starts Indexer using a commmand job
     *
     * @param Request $request
     * @return JsonResponse
     *
     */

    public function startIndexerAction(Request $request)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_exports'))){
            throw new AccessDeniedException();
        }
        /*if ($this->isPendingJob('silversolutions:importecontent')) {
            return new JsonResponse('Export still running');
        }
        if ($this->isPendingJob('silversolutions:indexecontent')) {
            return new JsonResponse('Export still running');
        }*/
        $job = new Job('silversolutions:indexecontent-job', array(""), true, JobController::QUEUENAME_IMPORT);
        $job->setMaxRuntime(36000);
        $em = $this->getEm();
        $em->persist($job);
        $em->flush($job);

        return new JsonResponse();
    }

    /**
     * Starts exports of econtent tables
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function startExportAction(Request $request)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_exports'))){
            throw new AccessDeniedException();
        }
        $name = $request->get('name');
        $name = preg_replace( '/[^a-z0-9]+/', '-', strtolower( $name ) );
        $job = new Job('silversolutions:exportecontent', array($name), true, JobController::QUEUENAME_IMPORT);
        $job->setMaxRuntime(3600);
        $em = $this->getEm();
        $em->persist($job);
        $em->flush($job);

        return new JsonResponse();
    }

    /**
     * Starts an import
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function startImportAction(Request $request)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_imports'))){
            throw new AccessDeniedException();
        }

        $name = $request->get('name');
        $jobImport = new Job('silversolutions:importecontent', array($name), true, JobController::QUEUENAME_IMPORT);
        $jobImport->setMaxRuntime(3600);
        $em = $this->getEm();
        $em->persist($jobImport);
        $em->flush($jobImport);

        $jobIndexer = new Job('silversolutions:indexecontent-job', array('--siteaccess=import'), true, JobController::QUEUENAME_IMPORT);
        $jobIndexer->setMaxRuntime(3600);
        $jobIndexer->addDependency($jobImport);
        $em = $this->getEm();
        $em->persist($jobIndexer);
        $em->flush($jobIndexer);

        $refreshJob = new Job('silversolutions:navigation_cache:refresh', array(), true, JobController::QUEUENAME_IMPORT);
        $refreshJob->setMaxRuntime(300);
        $refreshJob->addDependency($jobImport);
        $em = $this->getEm();
        $em->persist($refreshJob);
        $em->flush($refreshJob);


        return new JsonResponse('');
    }

    /**
     * Swap product catalog
     *
     * @return JsonResponse
     *
     */
    public function swapProductsTmpToLiveAction()
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_imports'))){
            throw new AccessDeniedException();
        }

        $jobSwapIndex = new Job('silversolutions:indexecontent-job', array('swap'), true, JobController::QUEUENAME_IMPORT);
        $jobSwapIndex->setMaxRuntime(3600);
        $em = $this->getEm();
        $em->persist($jobSwapIndex);
        $em->flush($jobSwapIndex);

        $jobSwapEcontent = new Job('silversolutions:econtent-tables-swap-job', array(), true, JobController::QUEUENAME_IMPORT);
        $jobSwapEcontent->setMaxRuntime(3600);
        $jobSwapEcontent->addDependency($jobSwapIndex);
        $em = $this->getEm();
        $em->persist($jobSwapEcontent);
        $em->flush($jobSwapEcontent);
        return new JsonResponse();

    }


    /**
     * Returns a list of econtent backup files
     *
     * @return JsonResponse
     */
    public function listBackupFilesAction() {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_imports'))){
            throw new AccessDeniedException();
        }
        $dirName = $this->configResolver->getParameter('backup_dir','siso_control_center');
        $message = '';

        $backupDir = $this->projectDir. '/' . $dirName;
        if (!is_dir($backupDir)) {
            return new JsonResponse(array(
                'msg' => 'Backup dir '.$backupDir.' not found!',
                'dir' => $backupDir,
            ));
        }
        $finder = new Finder();
        $finder->sortByChangedTime();
        $finder->files()->in($backupDir);
        $fileList = array();
        foreach ($finder as $file) {
            $fileTime = filemtime($file->getPath()."/".$file->getRelativePathName());

            $fileList[] = array(
                'name' => $file->getRelativePathName(),
                'date' => date ("Y-m-d H:i:s",$fileTime),
                'size' => $this->formatSize(filesize($file->getPath()."/".$file->getRelativePathName())),


            );
        }
        return new JsonResponse(array(
            'backup' => $fileList,
            'dir' => $backupDir,
            'msg' => $message));
    }

    /**
     * Returns manager for job queue system
     *
     * @return mixed
     *
     */
    private function getEm()
    {
        $registry = $this->registry;
        return $registry->getManagerForClass('JMSJobQueueBundle:Job');
    }

    /**
     * returns readable size
     *
     * @param $bytes
     * @param int $precision
     * @return string
     *
     */
    private function formatSize($bytes, $precision = 2) {

        $unit = ["B", "KB", "MB", "GB"];
        $exp = floor(log($bytes, 1024)) | 0;
        return round($bytes / (pow(1024, $exp)), $precision).$unit[$exp];
    }
}
