<?php

namespace Miaoxing\Queue;

use Miaoxing\App\Job\SendEmail;

class Plugin extends \Miaoxing\Plugin\BasePlugin
{
    protected $name = '队列';

    protected $description = '队列管理';
}
