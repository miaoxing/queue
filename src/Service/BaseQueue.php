<?php

namespace Miaoxing\Queue\Service;

use DateTime;

/**
 * 基于Laravel Queue简化的队列服务
 *
 * @property \Wei\Event $event
 * @link https://github.com/laravel/framework/tree/5.1/src/Illuminate/Queue
 */
abstract class BaseQueue extends \miaoxing\plugin\BaseService
{
    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $name = 'default';

    /**
     * @var string
     */
    protected $jobClass = '\Miaoxing\Queue\Service\BaseJob';

    /**
     * Push a new job onto the queue.
     *
     * @param  string $queue
     * @param  string $job
     * @param  mixed $data
     * @return mixed
     */
    public function pushOn($queue, $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  string $queue
     * @param  \DateTime|int $delay
     * @param  string $job
     * @param  mixed $data
     * @return mixed
     */
    public function laterOn($queue, $delay, $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Push an array of jobs with different jobs and same data.
     *
     * @param  array $jobs
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    public function bulk($jobs, $data = '', $queue = null)
    {
        foreach ((array)$jobs as $job) {
            $this->push($job, $data, $queue);
        }
    }

    /**
     * Push an array of jobs with different data and some jobs.
     *
     * @param string $job
     * @param array $data
     * @param string $queue
     */
    public function pushMulti($job, $data = [], $queue = null)
    {
        foreach ($data as $row) {
            $this->push($job, $row, $queue);
        }
    }

    /**
     * Returns the name of the default queue.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return string
     */
    protected function createPayload($job, $data = '', $queue = null)
    {
        return ['job' => $job, 'data' => $data];
    }

    /**
     * Set additional meta on a payload string.
     *
     * @param  array $payload
     * @param  string $key
     * @param  string $value
     * @return array
     */
    protected function setMeta($payload, $key, $value)
    {
        $payload[$key] = $value;
        return $payload;
    }

    /**
     * Create a job instance.
     *
     * @param string $payload
     * @param string|null $id
     * @return BaseJob
     */
    protected function createJob($payload, $id = null)
    {
        return new $this->jobClass([
            'wei' => $this->wei,
            'id' => $id,
            'queue' => $this,
            'payload' => $payload,
        ]);
    }

    /**
     * Calculate the number of seconds with the given delay.
     *
     * @param  \DateTime|int $delay
     * @return int
     */
    protected function getSeconds($delay)
    {
        if ($delay instanceof DateTime) {
            return max(0, $delay->getTimestamp() - $this->getTime());
        }
        return (int)$delay;
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
     * Release the job back into the queue
     * @param array $payload
     * @param int $delay
     */
    public function release(array $payload, $delay)
    {
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    abstract public function push($job, $data = '', $queue = null);

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string $payload
     * @param  string $queue
     * @param  array $options
     * @return mixed
     */
    abstract public function pushRaw($payload, $queue = null, array $options = []);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int $delay
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @return mixed
     */
    abstract public function later($delay, $job, $data = '', $queue = null);

    /**
     * Pop the next job off of the queue.
     *
     * @param  string $queue
     * @return BaseJob|null
     */
    abstract public function pop($queue = null);

    /**
     * Delete the job from the queue.
     *
     * @param array $payload
     * @param int|null $id
     */
    abstract public function delete($payload, $id = null);
}
