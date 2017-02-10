<?php

namespace Miaoxing\Queue;

use Miaoxing\App\Job\SendEmail;

class Plugin extends \miaoxing\plugin\BasePlugin
{
    protected $name = '队列';

    protected $description = '队列管理';
}
