<?php

namespace Miaoxing\Queue\Service;

use Miaoxing\Queue\Service\BaseQueue;
use Miaoxing\Plugin\Service\Db;
use Wei\Schema;

/**
 * @property Schema $schema
 * @property Db $db
 */
class DbQueue extends BaseQueue
{
    /**
     * The database table that holds the jobs.
     *
     * @var string
     */
    protected $table = 'queue_jobs';

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected $default = 'queue';

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
        $payload = json_encode($this->createPayload($job, $data), JSON_UNESCAPED_SLASHES);

        return $this->pushRaw($payload, $queue);
    }

    /**
     * {@inheritdoc}
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $availableAt = time();
        if (isset($options['delay'])) {
            $availableAt += $options['delay'];
        }

        $this->db->insert($this->table, [
            'queue' => $this->getQueue($queue),
            'payload' => $payload,
            'created_at' => date('Y-m-d H:i:s'),
            'available_at' => date('Y-m-d H:i:s', $availableAt),
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        $payload = json_encode($this->createPayload($job, $data), JSON_UNESCAPED_SLASHES);

        return $this->pushRaw($payload, $queue, ['delay' => $delay]);
    }

    /**
     * {@inheritdoc}
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $pdo = $this->db->getPdo();
        $pdo->beginTransaction();

        if ($job = $this->getNextAvailableJob($queue)) {
            $job = $this->markJobAsReserved($job);

            $pdo->commit();

            return $this->createJob($job['payload'], $job['id'], $queue);
        }

        $pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($payload, $id = null)
    {
        $this->db->delete($this->table, ['id' => $id]);
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    protected function getQueue($queue)
    {
        return $queue ?: $this->default;
    }

    /**
     * Get the next available job for the queue.
     *
     * @param  string|null  $queue
     * @return array|null
     */
    protected function getNextAvailableJob($queue)
    {
        $job = $this->db($this->table)
            ->where(['queue' => $this->getQueue($queue)])
            ->andWhere(
                // available or reserved but expired
                "(reserved_at = '0000-00-00 00:00:00' AND available_at <= ?) OR (reserved_at <= ?)",
                [date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() - $this->expire)]
            )
            ->asc('id')
            ->forUpdate()
            ->fetch();

        if ($job) {
            $job['payload'] = json_decode($job['payload'], true);
            $job['payload']['attempts'] = $job['attempts'];
        }

        return $job;
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param array $job
     * @return array
     */
    protected function markJobAsReserved($job)
    {
        $job['attempts'] += 1;
        $job['reserved_at'] = $this->getTime();

        $this->db($this->table)
            ->where(['id' => $job['id']])
            ->update([
                'reserved_at' => date('Y-m-d H:i:s', $job['reserved_at']),
                'attempts' => $job['attempts'],
            ]);

        return $job;
    }
}
