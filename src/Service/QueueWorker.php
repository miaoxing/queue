<?php

namespace Miaoxing\Queue\Service;

use Exception;
use miaoxing\plugin\BaseService;
use Miaoxing\Queue\Service\BaseQueue;
use Miaoxing\Queue\Service\BaseJob;

/**
 * @property \Wei\Logger $logger
 * @property \Wei\Event $event
 * @property \Wei\Cache $cache
 * @property \Wei\Db $db
 */
class QueueWorker extends BaseService
{
    /**
     * @var string|null
     */
    protected $queueName;

    /**
     * Run the worker in daemon mode
     *
     * @var bool
     */
    protected $daemon = false;

    /**
     * Number of seconds to sleep when no job is available
     *
     * @var int
     */
    protected $sleep = 3;

    /**
     * Number of times to attempt a job before logging it failed
     *
     * @var int
     */
    protected $tries = 1;

    /**
     * Amount of time to delay failed jobs
     *
     * @var int
     */
    protected $delay = 0;

    /**
     * The memory limit in megabytes
     *
     * @var int
     */
    protected $memory = 100;

    /**
     * The time limit to run a daemon
     *
     * @var int
     */
    protected $timeLimit = 1800;

    /**
     * Whether log failed jobs to database or not
     *
     * @var bool
     */
    protected $logFailedJobsToDb = true;

    /**
     * Run the worker instance.
     *
     * @param array $options
     * @return array
     */
    public function work(array $options = [])
    {
        $options && $this->setOption($options);

        if ($this->daemon) {
            return $this->daemon($this->queueName, $this->delay, $this->memory, $this->sleep, $this->tries);
        }

        return $this->pop($this->delay, $this->sleep, $this->tries);
    }

    /**
     * Restart queue worker daemons after their current job
     */
    public function restart()
    {
        $this->cache->set('wei:queue:restart', $this->getTime());
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param  string $queueName
     * @param  int $delay
     * @param  int $memory
     * @param  int $sleep
     * @param  int $maxTries
     * @return array
     */
    public function daemon($queueName = null, $delay = 0, $memory = 128, $sleep = 3, $maxTries = 0)
    {
        $startTime = $this->getTime();
        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {
            if ($this->daemonShouldRun()) {
                $this->runNextJobForDaemon($queueName, $delay, $sleep, $maxTries);
            } else {
                $this->sleep($sleep);
            }

            if ($this->timeExceeded($startTime, $this->timeLimit)
                || $this->memoryExceeded($memory)
                || $this->queueShouldRestart($lastRestart)) {
                $this->stop();
            }
        }
    }

    /**
     * Run the next job for the daemon worker.
     *
     * @param  string $queueName
     * @param  int $delay
     * @param  int $sleep
     * @param  int $maxTries
     * @return void
     */
    protected function runNextJobForDaemon($queueName, $delay, $sleep, $maxTries)
    {
        try {
            $this->pop($queueName, $delay, $sleep, $maxTries);
        } catch (Exception $e) {
            $this->logger->alert($e);
        }
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @return bool
     */
    protected function daemonShouldRun()
    {
        return $this->event->until('queueLooping') !== false;
    }

    /**
     * Listen to the given queue.
     *
     * @param  string $queueName
     * @param  int $delay
     * @param  int $sleep
     * @param  int $maxTries
     * @return array
     */
    public function pop($queueName, $delay = 0, $sleep = 3, $maxTries = 0)
    {
        $job = $this->getNextJob($this->queue, $queueName);

        // If we're able to pull a job off of the stack, we will process it and
        // then immediately return back out. If there is no job on the queue
        // we will "sleep" the worker for the specified number of seconds.
        if (!is_null($job)) {
            return $this->process($job, $maxTries, $delay);
        }

        $this->sleep($sleep);

        return ['job' => null, 'failed' => false];
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param BaseQueue $driver
     * @param  string $queueName
     * @return BaseJob|null
     */
    protected function getNextJob($driver, $queueName)
    {
        if (is_null($queueName)) {
            return $driver->pop();
        }

        foreach (explode(',', $queueName) as $queue) {
            if (!is_null($job = $driver->pop($queue))) {
                return $job;
            }
        }

        return null;
    }

    /**
     * Process a given job from the queue.
     *
     * @param  BaseJob $job
     * @param  int $maxTries
     * @param  int $delay
     * @return array|null
     * @throws Exception
     */
    public function process(BaseJob $job, $maxTries = 0, $delay = 0)
    {
        if ($maxTries > 0 && $job->attempts() > $maxTries) {
            return $this->logFailedJob($job);
        }

        try {
            // First we will fire off the job. Once it is done we will see if it will
            // be auto-deleted after processing and if so we will go ahead and run
            // the delete method on the job. Otherwise we will just keep moving.
            $job->fire();
            $this->raiseAfterJobEvent($job);

            return ['job' => $job, 'failed' => false];
        } catch (Exception $e) {
            // If we catch an exception, we will attempt to release the job back onto
            // the queue so it is not lost. This will let is be retried at a later
            // time by another listener (or the same one). We will do that here.
            if (!$job->isDeleted()) {
                $job->release($delay);
            }

            throw $e;
        }
    }

    /**
     * Raise the after queue job event.
     *
     * @param  BaseJob $job
     * @return void
     */
    protected function raiseAfterJobEvent(BaseJob $job)
    {
        $this->event->trigger('queueAfter', [$job, $job->getPayload()]);
    }

    /**
     * Log a failed job into storage.
     *
     * @param  BaseJob $job
     * @return array
     */
    protected function logFailedJob(BaseJob $job)
    {
        $this->logger->alert('Queue job failed', $job->getPayload());

        if ($this->logFailedJobsToDb) {
            $this->db->insert('queue_failed_jobs', [
                'queue' => $job->queue->getName(),
                'payload' => json_encode($job->getPayload(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s', $this->getTime()),
            ]);
        }

        $job->delete();
        $job->failed();
        $this->raiseFailedJobEvent($job);

        return ['job' => $job, 'failed' => true];
    }

    /**
     * Raise the failed queue job event.
     *
     * @param  BaseJob $job
     * @return void
     */
    protected function raiseFailedJobEvent(BaseJob $job)
    {
        $this->event->trigger('queueFailed', [$job, $job->getPayload()]);
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param  int $memoryLimit
     * @return bool
     */
    public function memoryExceeded($memoryLimit)
    {
        return (memory_get_usage() / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Determine if the time limit has been exceeded.
     *
     * @param int $startTime
     * @param int $timeLimit
     * @return bool
     */
    public function timeExceeded($startTime, $timeLimit)
    {
        return $this->getTime() - $startTime > $timeLimit;
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @return void
     */
    public function stop()
    {
        $this->event->trigger('queueStopping');
        die;
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param  int $seconds
     * @return void
     */
    public function sleep($seconds)
    {
        sleep($seconds);
    }

    /**
     * Get the current UNIX timestamp.
     *
     * @return int
     */
    protected function getTime()
    {
        return time();
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart()
    {
        return $this->cache->get('wei:queue:restart');
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param  int|null $lastRestart
     * @return bool
     */
    protected function queueShouldRestart($lastRestart)
    {
        return $this->getTimestampOfLastQueueRestart() != $lastRestart;
    }
}
