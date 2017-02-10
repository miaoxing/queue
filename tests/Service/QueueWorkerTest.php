<?php

namespace MiaoxingTest\Queue\Service;

use RuntimeException;

/**
 * @link https://github.com/laravel/framework/blob/5.1/tests/Queue/QueueWorkerTest.php
 */
class QueueWorkerTest extends \Miaoxing\Plugin\Test\BaseTestCase
{
    public function testJobIsPoppedOffQueueAndProcessed()
    {
        $queue = $this->getServiceMock('queue', ['pop']);

        $worker = $this->getServiceMock('queueWorker', ['process'], [[
            'queue' => $queue,
        ]]);

        $job = $this->getMock('\services\BaseJob');

        $queue->expects($this->once())
            ->method('pop')
            ->with('queue')
            ->willReturn($job);

        $worker->expects($this->once())
            ->method('process')
            ->with($job, 0, 0);

        $worker->pop('queue', 0, 0);
    }

    public function testJobIsPoppedOffFirstQueueInListAndProcessed()
    {
        $queue = $this->getServiceMock('queue', ['pop']);

        $worker = $this->getServiceMock('queueWorker', ['process'], [[
            'queue' => $queue,
        ]]);

        $job = $this->getMock('\services\BaseJob');

        $queue->expects($this->at(0))
            ->method('pop')
            ->with('queue1')
            ->willReturn(null);

        $queue->expects($this->at(1))
            ->method('pop')
            ->with('queue2')
            ->willReturn($job);

        $worker->expects($this->once())
            ->method('process')
            ->with($job, 0, 0);

        $worker->pop('queue1,queue2', 0, 0);
    }

    public function testWorkerSleepsIfNoJobIsPresentAndSleepIsEnabled()
    {
        $queue = $this->getServiceMock('queue', ['pop']);

        $worker = $this->getServiceMock('queueWorker', ['process', 'sleep'], [[
            'queue' => $queue,
        ]]);

        $queue->expects($this->once())
            ->method('pop')
            ->with('queue')
            ->willReturn(null);

        $worker->expects($this->never())
            ->method('process');

        $worker->expects($this->once())
            ->method('sleep')
            ->with(2);

        $worker->pop('queue', 0, 2);
    }

    public function testWorkerLogsJobToFailedQueueIfMaxTriesHasBeenExceeded()
    {
        $queue = $this->getServiceMock('queue', ['pop']);

        $db = $this->getServiceMock('db', ['insert']);

        $worker = $this->getServiceMock('queueWorker', ['sleep', 'getTime'], [[
            'queue' => $queue,
            'db' => $db,
            'logFailedJobsToDb' => true,
        ]]);

        $time = time();
        $worker->expects($this->once())
            ->method('getTime')
            ->willReturn($time);

        $job = $this->getMock('\services\BaseJob', ['attempts', 'getPayload', 'delete', 'failed'], [[
            'wei' => $this->wei,
            'queue' => $queue,
        ]]);

        $job->expects($this->once())
            ->method('attempts')
            ->willReturn(10);

        $job->expects($this->exactly(3))
            ->method('getPayload')
            ->willReturn('body');

        $job->expects($this->once())
            ->method('delete');

        $job->expects($this->once())
            ->method('failed');

        $db->expects($this->once())
            ->method('insert')
            ->with('failedJobs', [
                'queue' => 'default',
                'payload' => '"body"',
                'createTime' => date('Y-m-d H:i:s', $time),
            ]);

        $worker->process($job, 3, 0);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testJobIsReleasedWhenExceptionIsThrown()
    {
        $job = $this->getMock('\services\BaseJob');

        $job->expects($this->once())
            ->method('fire')
            ->willReturnCallback(function () {
                throw new RuntimeException();
            });

        $job->expects($this->once())
            ->method('isDeleted')
            ->willReturn(false);

        $job->expects($this->once())
            ->method('release')
            ->with(5);

        wei()->queueWorker->process($job, 0, 5);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testJobIsNotReleasedWhenExceptionIsThrownButJobIsDeleted()
    {
        $job = $this->getMock('\services\BaseJob');

        $job->expects($this->once())
            ->method('fire')
            ->willReturnCallback(function () {
                throw new RuntimeException();
            });

        $job->expects($this->once())
            ->method('isDeleted')
            ->willReturn(true);

        $job->expects($this->never())
            ->method('release');

        wei()->queueWorker->process($job, 0, 5);
    }
}
