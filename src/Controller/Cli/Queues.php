<?php

namespace Miaoxing\Queue\Controller\Cli;

class Queues extends \miaoxing\plugin\BaseController
{
    public function workAction($req)
    {
        wei()->queueWorker->work([
            'queueName' => $req['queue-name']
        ]);

        return $this->suc();
    }

    public function daemonAction($req)
    {
        wei()->queueWorker->work([
            'daemon' => true,
            'queueName' => $req['queue-name']
        ]);
    }

    public function restartAction()
    {
        wei()->queueWorker->restart();

        return $this->suc();
    }
}
