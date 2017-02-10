<?php

namespace MiaoxingTest\Queue\Fixture;

use Miaoxing\Queue\Job;
use Miaoxing\Queue\Service\BaseJob;

class FailingSyncQueueTestHandler extends Job
{
    public function __invoke(BaseJob $job, $data)
    {
        throw new \Exception();
    }

    public function failed()
    {
        $_SERVER['__sync.failed'] = true;
    }
}
