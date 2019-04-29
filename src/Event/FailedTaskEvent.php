<?php

namespace hiqdev\recon\core\Event;

use Throwable;

class FailedTaskEvent extends TaskEvent
{
    /**
     * @var Throwable
     */
    protected $exception;

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function setException(Throwable $exception): self
    {
        $this->exception = $exception;

        return $this;
    }
}
