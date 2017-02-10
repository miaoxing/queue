<?php

namespace Miaoxing\Queue\Controller;

use Miaoxing\App\Job\SendEmail;

class Queues extends \miaoxing\plugin\BaseController
{
    protected $guestPages = [
        'queues'
    ];

    public function testAction($req)
    {
        $id = wei()->queue->push(SendEmail::className(), [
            'to' => 'to@example.com',
            'content' => 'hello'
        ]);
        return $id;
    }

    public function workAction($req)
    {
        wei()->queueWorker->work();
        return $this->suc();
    }

    public function daemonAction($req)
    {
        wei()->queueWorker->work([
            'daemon' => true
        ]);
    }

    public function restartAction($req)
    {
        wei()->queueWorker->restart();
        return $this->suc();
    }
}
