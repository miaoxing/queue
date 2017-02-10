<?php

namespace Miaoxing\Queue\Service;

class SyncQueue extends BaseQueue
{
    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueJob = $this->createJob($this->createPayload($job, $data, $queue));

        try {
            $queueJob->fire();
            $this->raiseAfterJobEvent($queueJob);
        } catch (\Exception $e) {
            $this->handleFailedJob($queueJob);
            throw $e;
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function delete($payload)
    {
    }

    /**
     * Raise the after queue job event.
     *
     * @param  BaseJob $job
     * @return void
     */
    protected function raiseAfterJobEvent(BaseJob $job)
    {
        $this->event->trigger('queueAfter', ['sync', $job, $job->getPayload()]);
    }

    /**
     * Handle the failed job.
     *
     * @param  BaseJob $job
     * @return array
     */
    protected function handleFailedJob(BaseJob $job)
    {
        $job->failed();
        $this->raiseFailedJobEvent($job);
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  BaseJob $job
     * @return void
     */
    protected function raiseFailedJobEvent(BaseJob $job)
    {
        $this->event->trigger('queueFailed', ['sync', $job, $job->getPayload()]);
    }
}
