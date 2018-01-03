<?php

namespace MiaoxingTest\Queue\Service;

use MiaoxingTest\Queue\Fixture\FailingSyncQueueTestHandler;

/**
 * @link https://github.com/laravel/framework/blob/5.1/tests/Queue/QueueSyncQueueTest.php
 */
class SyncQueueTest extends \Miaoxing\Plugin\Test\BaseTestCase
{
    public function testFailedJobGetsHandledWhenAnExceptionIsThrown()
    {
        unset($_SERVER['__sync.failed']);
        $sync = wei()->syncQueue;

        $event = $this->getServiceMock('event', ['trigger']);
        $event->expects($this->once())
            ->method('trigger')
            ->with('queueFailed');

        try {
            $sync->push(FailingSyncQueueTestHandler::class, ['foo' => 'bar']);
        } catch (\Exception $e) {
            $this->assertTrue($_SERVER['__sync.failed']);
        }
    }
}
