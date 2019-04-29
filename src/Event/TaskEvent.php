<?php

namespace hiqdev\recon\core\Event;

use hiapi\event\AbstractEvent;
use hiapi\event\NamedEventTrait;
use hiqdev\recon\core\Command\IncomingTask;

class TaskEvent extends AbstractEvent
{
    use NamedEventTrait;

    public function getTarget(): IncomingTask
    {
        return parent::getTarget();
    }
}
