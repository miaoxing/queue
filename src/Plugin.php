<?php

namespace Miaoxing\Queue;

use Miaoxing\App\Job\SendEmail;

class Plugin extends \miaoxing\plugin\BasePlugin
{
    protected $name = '队列';

    protected $description = '队列管理';

    public function onPreControllerInit()
    {
        // 测试队列
        if ($this->app->getController() == 'index') {
            try {
                $this->queue->push(SendEmail::className(), [
                    'note' => date('m-d H:i:s'),
                    'appId' => $this->app->getId()
                ]);
            } catch (\Exception $e) {
                wei()->logger->alert($e);
            }
        }
    }
}
