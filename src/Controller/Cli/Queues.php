<?php

namespace Miaoxing\Queue\Controller\Cli;

class Queues extends \miaoxing\plugin\BaseController
{
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
