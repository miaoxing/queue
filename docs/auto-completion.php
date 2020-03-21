<?php

/**
 * @property    Miaoxing\Queue\Service\Queue $queue
 */
class QueueMixin {
}

/**
 * @mixin QueueMixin
 */
class AutoCompletion {
}

/**
 * @return AutoCompletion
 */
function wei()
{
    return new AutoCompletion;
}

/** @var Miaoxing\Queue\Service\Queue $queue */
$queue = wei()->queue;
