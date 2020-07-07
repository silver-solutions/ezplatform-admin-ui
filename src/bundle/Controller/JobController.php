<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Commerce\Bundle\AdminUiBundle\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use JMS\JobQueueBundle\Entity\Repository\JobManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use eZ\Publish\Core\MVC\Symfony\Security\Authorization\Attribute as AuthorizationAttribute;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManager;
use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\View\JobFilter;

class JobController extends AbstractController
{
    CONST QUEUENAME_IMPORT = 'productimport';

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface */
    private $configResolver;

    /** @var \JMS\JobQueueBundle\Entity\Repository\JobManager */
    private $jobManager;

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
    private $registry;

    public function __construct(
        ConfigResolverInterface $configResolver,
        JobManager $jobManager,
        Registry $registry
    ) {
        $this->configResolver = $configResolver;
        $this->jobManager = $jobManager;
        $this->registry = $registry;
    }

    /**
     * Returns Jobs as json
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function jobListDataAction(Request $request)
    {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_jobs'))){
            throw new AccessDeniedException();
        }
        $jobFilter = JobFilter::fromRequest($request);
        $jobs = $this->getJobs($jobFilter);

        return New JsonResponse(array(
            'jobs' => $this->convertJobs($jobs),
            'addJobs' => $this->configResolver->getParameter('add_jobs','siso_control_center'),
            'importAllowed' => !$this->isPendingJob('silversolutions:indexecontent') &&
                !$this->isPendingJob('silversolutions:importecontent')
            )
        );

    }

    /**
     * Starts a new job defined in config
     *
     * @param Request $request
     * @return JsonResponse
     *
     */
    public function startJobAction(Request $request)
    {

        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_jobs'))){
            throw new AccessDeniedException();
        }
        $jobid = $request->get('job_id');

        $addJobList = $this->configResolver->getParameter('add_jobs','siso_control_center');

        if (!isset($addJobList[$jobid])) {
            throw new AccessDeniedException();
        }

        $cmdDetails = $addJobList[$jobid];
        if (!isset($cmdDetails['params'])) {
            $cmdDetails['params'] = array();
        }
        $job = new Job($cmdDetails['command'], $cmdDetails['params'], true, JobController::QUEUENAME_IMPORT);
        $job->setMaxRuntime(3600);
        $em = $this->getEm();
        $em->persist($job);
        $em->flush($job);

        if (!isset($addJobList[$jobid]['depending_jobs'])) {
            return new JsonResponse();
        }

        foreach ($addJobList[$jobid]['depending_jobs'] as $definedJob) {
            $cmdDetails = $definedJob;
            if (!isset($cmdDetails['params'])) {
                $cmdDetails['params'] = array();
            }

            $dependingJob = new Job($cmdDetails['command'], $cmdDetails['params'], true, JobController::QUEUENAME_IMPORT);
            $dependingJob->setMaxRuntime(3600);
            $dependingJob->addDependency($job);
            $em = $this->getEm();
            $em->persist($dependingJob);

            $em->flush($dependingJob);
        }


        return new JsonResponse();
    }

    /**
     * Removes a job
     *
     * @param $id
     * @return JsonResponse
     *
     */
    public function removeJobAction($id) {
        if (!$this->isGranted(new AuthorizationAttribute('siso_control_center', 'manage_jobs'))){
            throw new AccessDeniedException();
        }
        try {
            $jobList = $this->getEm()->createQuery("SELECT j FROM JMSJobQueueBundle:Job j WHERE j.id=:id")
                ->setParameter('id', $id)
                ->setMaxResults(1)
                ->getResult();
            $this->resolveDependencies($this->getEm(),$jobList[0]);
            $this->getEm()->remove($jobList[0]);
            $this->getEm()->persist($jobList[0]);
            $this->getEm()->flush($jobList[0]);
            $this->getEm()->getConnection()->executeUpdate("DELETE FROM jms_jobs WHERE id = :id", array('id' => $id));
        } catch (\Exception $e) {

        }
        return new JsonResponse();
    }

    private function resolveDependencies(EntityManager $em, Job $job)
    {
        // If this job has failed, or has otherwise not succeeded, we need to set the
        // incoming dependencies to failed if that has not been done already.
        if ( ! $job->isFinished()) {
            /** @var JobRepository $repository */
            $repository = $em->getRepository(Job::class);
            foreach ($repository->findIncomingDependencies($job) as $incomingDep) {
                if ($incomingDep->isInFinalState()) {
                    continue;
                }

                $finalState = Job::STATE_CANCELED;
                if ($job->isRunning()) {
                    $finalState = Job::STATE_FAILED;
                }

                $repository->closeJob($incomingDep, $finalState);
            }
        }

        $em->getConnection()->executeUpdate("DELETE FROM jms_job_dependencies WHERE dest_job_id = :id", array('id' => $job->getId()));
    }


    /**
     * get last jobs
     *
     * @param $jobFilter
     * @return mixed
     *
     */
    protected function getJobs($jobFilter)
    {
        $qb = $this->getEm()->createQueryBuilder();
        $qb->select('j')->from('JMSJobQueueBundle:Job', 'j')
            ->where($qb->expr()->isNull('j.originalJob'))
            ->orderBy('j.id', 'desc');

        $lastJobsWithError = $jobFilter->isDefaultPage() ? $this->jobManager->findLastJobsWithError(5) : [];
        foreach ($lastJobsWithError as $i => $job) {
            $qb->andWhere($qb->expr()->neq('j.id', '?'.$i));
            $qb->setParameter($i, $job->getId());
        }

        if ( ! empty($jobFilter->command)) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('j.command', ':commandQuery'),
                $qb->expr()->like('j.args', ':commandQuery')
            ))
                ->setParameter('commandQuery', '%'.$jobFilter->command.'%');
        }

        if ( ! empty($jobFilter->state)) {
            $qb->andWhere($qb->expr()->eq('j.state', ':jobState'))
                ->setParameter('jobState', $jobFilter->state);
        }

        $perPage = 50;

        $query = $qb->getQuery();
        $query->setMaxResults($perPage + 1);
        $query->setFirstResult(($jobFilter->page - 1) * $perPage);

        return $query->getResult();
    }



    /**
     * Checks if job is pending or running
     *
     * @param $cmdName
     * @return bool
     *
     */
    protected function isPendingJob($cmdName)
    {
        $jobs = $this->getJobs(JobFilter::fromRequest(New Request));
        foreach ($jobs as $job) {
            if (($job->getCommand() == $cmdName) &&
                in_array($job->getState(), array('pending','running'))
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts joblist to array format
     *
     * @param array $jobs
     * @return array
     *
     */
    protected function convertJobs(array $jobs)
    {
        $jobList = array();
        foreach ($jobs as $job) {
            $jobList[] = array(
                'id' => $job->getId(),
                'command' => $job->getCommand(),
                'args' => implode(' ',$job->getArgs()),

                'state' => $job->getState(),
                'createdAt' =>  $this->formatDate($job->getCreatedAt()),
                'startedAt' => $this->formatDate($job->getStartedAt()),
                'runtime' => $job->getRuntime(),
                'queue' => $job->getQueue(),
                'output' => $job->getOutput(),
                'error' => $job->getErrorOutput(),
                'exitCode' => $job->getExitCode()

            );
        }
        return $jobList;
    }

    protected function formatDate($datetime)
    {
        if ($datetime == null) {
            return "-";
        }
        return $datetime->format('Y-m-d H:i:s');
    }

    /**
     *
     *
     * @return mixed
     *
     */
    private function getEm()
    {
        return $this->registry->getManagerForClass('JMSJobQueueBundle:Job');
    }
}
