<?php

namespace Miaoxing\Queue\Service;

use Miaoxing\Queue\Service\BaseQueue;
use services\Db;
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
        $this->db->insert($this->table, [
            'queue' => $this->getQueue($queue),
            'payload' => $payload,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        // TODO: Implement later() method.
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

            return $this->createJob($job['payload'], $job['id']);
        }

        $pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($payload, $id)
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
            ->where([
                'queue' => $this->getQueue($queue),
                'reserved_at' => '0000-00-00 00:00:00'
            ])
            ->andWhere('available_at <= ?', date('Y-m-d H:i:s'))
            ->asc('id')
            ->find();

        if ($job) {
            $job['payload'] = json_decode($job['payload'], true);
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
                'reserved_at' => $job['reserved_at'],
                'attempts' => $job['attempts'],
            ]);

        return $job;
    }
}
