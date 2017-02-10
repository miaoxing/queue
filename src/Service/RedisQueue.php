<?php

namespace Miaoxing\Queue\Service;

/**
 * @property \Wei\Redis $redis
 * @method \Redis redis()
 */
class RedisQueue extends BaseQueue
{
    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected $expire = 60;

    /**
     * {@inheritdoc}
     */
    public function push($job, $data = '', $queue = null)
    {
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $this->redis()->rpush($this->getQueue($queue), $payload);
        return $payload['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = $this->createPayload($job, $data);

        $delay = $this->getSeconds($delay);

        $this->redis()->zadd($this->getQueue($queue) . ':delayed', $this->getTime() + $delay, $payload);

        return $payload['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function release(array $payload, $delay)
    {
        $payload = $this->setMeta($payload, 'attempts', $payload['attempts'] + 1);
        $this->redis()->zadd($this->getQueue() . ':delayed', $this->getTime() + $delay, $payload);
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        if (!is_null($this->expire)) {
            $this->migrateAllExpiredJobs($queue);
        }

        $payload = $this->redis()->lpop($queue);
        if ($payload) {
            $this->redis()->zadd($queue . ':reserved', $this->getTime() + $this->expire, $payload);
            return $this->createJob($payload);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($payload, $id = null)
    {
        $this->redis()->zrem($this->getQueue() . ':reserved', $payload);
    }

    /**
     * Migrate all of the waiting jobs in the queue.
     *
     * @param  string $queue
     * @return void
     */
    protected function migrateAllExpiredJobs($queue)
    {
        $this->migrateExpiredJobs($queue . ':delayed', $queue);
        $this->migrateExpiredJobs($queue . ':reserved', $queue);
    }

    /**
     * Migrate the delayed jobs that are ready to the regular queue.
     *
     * @param  string $from
     * @param  string $to
     * @return void
     */
    public function migrateExpiredJobs($from, $to)
    {
        // TODO Use transaction
        // First we need to get all of jobs that have expired based on the current time
        // so that we can push them onto the main queue. After we get them we simply
        // remove them from this "delay" queues. All of this within a transaction.
        $jobs = $this->getExpiredJobs($from, $time = $this->getTime());

        // If we actually found any jobs, we will remove them from the old queue and we
        // will insert them onto the new (ready) "queue". This means they will stand
        // ready to be processed by the queue worker whenever their turn comes up.
        if (count($jobs) > 0) {
            $this->removeExpiredJobs($from, $time);
            $this->pushExpiredJobsOntoNewQueue($to, $jobs);
        }
    }

    /**
     * Get the expired jobs from a given queue.
     *
     * @param  string $from
     * @param  int $time
     * @return array
     */
    protected function getExpiredJobs($from, $time)
    {
        return $this->redis()->zrangebyscore($from, '-inf', $time);
    }

    /**
     * Remove the expired jobs from a given queue.
     *
     * @param  string $from
     * @param  int $time
     * @return void
     */
    protected function removeExpiredJobs($from, $time)
    {
        $this->redis()->zremrangebyscore($from, '-inf', $time);
    }

    /**
     * Push all of the given jobs onto another queue.
     *
     * @param  string $to
     * @param  array $jobs
     * @return void
     */
    protected function pushExpiredJobsOntoNewQueue($to, $jobs)
    {
        call_user_func_array([$this->redis(), 'rpush'], array_merge([$to], $jobs));
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
        $payload = parent::createPayload($job, $data);
        $payload = $this->setMeta($payload, 'id', $this->getRandomId());
        return $this->setMeta($payload, 'attempts', 1);
    }

    /**
     * Get a random ID string.
     *
     * @return string
     */
    protected function getRandomId()
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < 32; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null $name
     * @return string
     */
    protected function getQueue($name = null)
    {
        return 'queues:' . ($name ?: $this->name);
    }

    /**
     * Get the expiration time in seconds.
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * Set the expiration time in seconds.
     *
     * @param  int|null $seconds
     * @return void
     */
    public function setExpire($seconds)
    {
        $this->expire = $seconds;
    }
}
