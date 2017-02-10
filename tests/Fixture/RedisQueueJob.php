<?php

namespace MiaoxingTest\Queue\Fixture;

use Miaoxing\Queue\Service\BaseJob;

class RedisQueueJob extends BaseJob
{
    /**
     * {@inheritdoc}
     */
    public function __invoke()
    {
    }
}
