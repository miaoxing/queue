<?php

namespace Miaoxing\Queue\Service;

class Queue extends DbQueue
{
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->default = wei()->app->getNamespace();
    }
}
