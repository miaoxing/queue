<?php

namespace MiaoxingDoc\Queue {

    /**
     * @property    \Miaoxing\Queue\Service\BaseJob $baseJob
     *
     * @property    \Miaoxing\Queue\Service\BaseQueue $baseQueue 基于Laravel Queue简化的队列服务
     *
     * @property    \Miaoxing\Queue\Service\DbQueue $dbQueue
     *
     * @property    \Miaoxing\Queue\Service\Queue $queue
     *
     * @property    \Miaoxing\Queue\Service\QueueWorker $queueWorker
     *
     * @property    \Miaoxing\Queue\Service\RedisQueue $redisQueue
     *
     * @property    \Miaoxing\Queue\Service\SyncQueue $syncQueue
     */
    class AutoComplete
    {
    }
}

namespace {

    /**
     * @return MiaoxingDoc\Queue\AutoComplete
     */
    function wei()
    {
    }

    /** @var Miaoxing\Queue\Service\BaseJob $baseJob */
    $baseJob = wei()->baseJob;

    /** @var Miaoxing\Queue\Service\BaseQueue $baseQueue */
    $baseQueue = wei()->baseQueue;

    /** @var Miaoxing\Queue\Service\DbQueue $dbQueue */
    $dbQueue = wei()->dbQueue;

    /** @var Miaoxing\Queue\Service\Queue $queue */
    $queue = wei()->queue;

    /** @var Miaoxing\Queue\Service\QueueWorker $queueWorker */
    $queueWorker = wei()->queueWorker;

    /** @var Miaoxing\Queue\Service\RedisQueue $redisQueue */
    $redisQueue = wei()->redisQueue;

    /** @var Miaoxing\Queue\Service\SyncQueue $syncQueue */
    $syncQueue = wei()->syncQueue;
}
