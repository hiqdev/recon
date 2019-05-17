<?php

namespace hiqdev\recon\core\Exception;

/**
 * Class DeferTaskException represents an error, that is not fatal
 * and thus task can be retried later.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class DeferTaskException extends ReconException
{
    /** @var int delay in seconds */
    private $delay = 60;

    public function setDelay(int $delay): self
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }
}
