<?php

namespace hiqdev\recon\core\Command;

use hiqdev\recon\core\Service\TaskLifecycle;

class IncomingTaskHandler
{
    /**
     * @var TaskLifecycle
     */
    private $lifecycle;

    public function __construct(TaskLifecycle $lifecycle)
    {
        $this->lifecycle = $lifecycle;
    }

    public function handle(IncomingTask $task): void
    {
        $this->lifecycle->run($task);
    }
}
