<?php

namespace hiqdev\recon\core\Model;

use hiapi\commands\BaseCommand;

final class SimpleCommandHandlingResult implements CommandHandlingResultInterface
{
    /**
     * @var BaseCommand|null
     */
    private $command;
    /**
     * @var string|null
     */
    public $stdout;
    /**
     * @var string|null
     */
    public $stderr;
    /**
     * @var string|null
     */
    public $remoteId;
    /**
     * @var boolean|null. Defaults to boolean `true`
     */
    public $isSuccess = true;

    public function __construct(?BaseCommand $command)
    {
        $this->command = $command;
    }

    /** {@inheritDoc} */
    public function command(): ?BaseCommand
    {
        return $this->command;
    }

    /** {@inheritDoc} */
    public function stdout(): ?string
    {
        return $this->stdout;
    }

    /** {@inheritDoc} */
    public function stderr(): ?string
    {
        return $this->stderr;
    }

    /** {@inheritDoc} */
    public function remoteId(): ?string
    {
        return $this->remoteId;
    }

    /** {@inheritDoc} */
    public function isSuccess(): ?bool
    {
        return $this->isSuccess;
    }

    public function continuesExecutionInBackground(?string $remote_id): self
    {
        $this->isSuccess = null;
        $this->remoteId = $remote_id;

        return $this;
    }
}
