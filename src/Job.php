<?php

namespace Miaoxing\Queue;

use Miaoxing\Queue\Service\BaseJob;

/**
 * @property \Wei\Logger $logger
 */
abstract class Job extends \Miaoxing\Plugin\BaseService
{
    abstract  public function __invoke(BaseJob $job, $data);
}
