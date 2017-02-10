<?php

namespace MiaoxingTest\Queue\Service;

/**
 * @link https://github.com/laravel/framework/blob/5.1/tests/Queue/QueueRedisQueueTest.php
 */
class RedisQueueTest extends \Miaoxing\Plugin\Test\BaseTestCase
{
    public function testPushProperlyPushesJobOntoRedis()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $queue = $this->getServiceMock('redisQueue', ['getRandomId'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $redis->expects($this->once())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $phpRedis->expects($this->once())
            ->method('rpush')
            ->with('queues:default', ['job' => 'foo', 'data' => ['data'], 'id' => 'foo', 'attempts' => 1]);

        $id = $queue->push('foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushProperlyPushesJobOntoRedis()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->once())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getSeconds', 'getTime', 'getRandomId'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $queue->expects($this->once())
            ->method('getSeconds')
            ->with(1)
            ->willReturn(1);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $phpRedis->expects($this->once())
            ->method('zadd')
            ->with(
                'queues:default:delayed',
                2,
                ['job' => 'foo', 'data' => ['data'], 'id' => 'foo', 'attempts' => 1]
            );

        $id = $queue->later(1, 'foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testDelayedPushWithDateTimeProperlyPushesJobOntoRedis()
    {
        $date = new \DateTime();

        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->once())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getSeconds', 'getTime', 'getRandomId'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getRandomId')
            ->willReturn('foo');

        $queue->expects($this->once())
            ->method('getSeconds')
            ->with($date)
            ->willReturn(1);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $phpRedis->expects($this->once())
            ->method('zadd')
            ->with(
                'queues:default:delayed',
                2,
                ['job' => 'foo', 'data' => ['data'], 'id' => 'foo', 'attempts' => 1]
            );

        $id = $queue->later($date, 'foo', ['data']);
        $this->assertEquals('foo', $id);
    }

    public function testPopProperlyPopsJobOffOfRedis()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->any())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getTime', 'migrateAllExpiredJobs'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $queue->expects($this->once())
            ->method('migrateAllExpiredJobs')
            ->with('queues:default');

        $phpRedis->expects($this->once())
            ->method('lpop')
            ->with('queues:default')
            ->willReturn([
                'job' => 'MiaoxingTest\Queue\Fixture\RedisQueueJob',
                'data' => [],
            ]);

        $phpRedis->expects($this->once())
            ->method('zadd')
            ->with('queues:default:reserved', 61, [
                'job' => 'MiaoxingTest\Queue\Fixture\RedisQueueJob',
                'data' => [],
            ]);

        $result = $queue->pop();
        $this->assertInstanceOf('Miaoxing\Queue\Service\BaseJob', $result);
    }

    public function testReleaseMethod()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->any())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getTime'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $phpRedis->expects($this->once())
            ->method('zadd')
            ->with('queues:default:delayed', 2, ['attempts' => 2]);

        $queue->release(['attempts' => 1], 1);
    }

    public function testMigrateExpiredJobs()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->any())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getTime'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $phpRedis->expects($this->once())
            ->method('zrangebyscore')
            ->with('from', '-inf', 1)
            ->willReturn(['foo', 'bar']);

        $phpRedis->expects($this->once())
            ->method('zremrangebyscore')
            ->with('from', '-inf', 1);

        $phpRedis->expects($this->once())
            ->method('rpush')
            ->with('to', 'foo', 'bar');

        $queue->migrateExpiredJobs('from', 'to');
    }

    public function testNotExpireJobsWhenExpireNull()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->any())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getTime', 'migrateAllExpiredJobs'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $queue->expects($this->never())
            ->method('migrateAllExpiredJobs');

        $phpRedis->expects($this->once())
            ->method('lpop')
            ->with('queues:default')
            ->willReturn(['job' => 'MiaoxingTest\Queue\Fixture\RedisQueueJob', 'data' => []]);

        $phpRedis->expects($this->once())
            ->method('zadd')
            ->with('queues:default:reserved', 1, ['job' => 'MiaoxingTest\Queue\Fixture\RedisQueueJob', 'data' => []]);

        $queue->setExpire(null);
        $queue->pop();
    }

    public function testExpireJobsWhenExpireSet()
    {
        $phpRedis = $this->getMock('Redis');

        $redis = $this->getServiceMock('redis', ['__invoke'], [[
            'object' => $phpRedis,
        ]]);

        $redis->expects($this->any())
            ->method('__invoke')
            ->willReturn($phpRedis);

        $queue = $this->getServiceMock('redisQueue', ['getTime', 'migrateAllExpiredJobs'], [[
            'redis' => $redis,
        ]]);

        $queue->expects($this->once())
            ->method('getTime')
            ->willReturn(1);

        $queue->expects($this->once())
            ->method('migrateAllExpiredJobs')
            ->with('queues:default');

        $phpRedis->expects($this->once())
            ->method('lpop')
            ->with('queues:default')
            ->willReturn(['job' => 'MiaoxingTest\Queue\Fixture\RedisQueueJob', 'data' => []]);

        $phpRedis->expects($this->once())
            ->method('zadd')
            ->with('queues:default:reserved', 31, ['job' => 'MiaoxingTest\Queue\Fixture\RedisQueueJob', 'data' => []]);

        $queue->setExpire(30);
        $queue->pop();
    }
}
