<?php

namespace Miaoxing\Queue\Service;

use DateTime;

/**
 * @property BaseQueue $queue
 */
class BaseJob extends \Miaoxing\Plugin\BaseService
{
    /**
     * @var array
     */
    protected $payload = [];

    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected $queueName;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected $released = false;

    /**
     * @var int
     */
    protected $id;

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->instance = new $this->payload['job']([
            'wei' => $this->wei,
        ]);
        $this->instance->__invoke($this, $this->payload['data']);
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->queue->delete($this->payload, $this->id);
        $this->deleted = true;
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->queue->release($this->payload, $delay);
        $this->released = true;
    }

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->released;
    }

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased()
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->payload['attempts'];
    }

    /**
     * Get the payload array for the job.
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Call when the job is failed
     *
     * @return void
     */
    public function failed()
    {
        if (method_exists($this->instance, 'failed')) {
            $this->instance->failed();
        }
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
     * Get the current system time.
     *
     * @return int
     */
    protected function getTime()
    {
        return time();
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName()
    {
        return $this->payload['job'];
    }

    /**
     * Get the name of current queue
     *
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }
}
