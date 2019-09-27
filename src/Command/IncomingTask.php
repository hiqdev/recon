<?php

namespace hiqdev\recon\core\Command;

use hiapi\commands\BaseCommand;
use hiqdev\recon\core\Model\CommandHandlingResultInterface;
use hiqdev\recon\core\Service\TaskResolverInterface;

/**
 * Class IncomingTask
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class IncomingTask extends BaseCommand
{
    /**
     * @var string|int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var CommandHandlingResultInterface|null
     */
    private $result;
    /**
     * @var TaskResolverInterface
     */
    private $resolver;

    /**
     * @var BaseCommand|null
     */
    private $command;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['id', 'name'], 'string'],
            [['id', 'name'], 'required'],
        ];
    }

    public function resolveToCommand(BaseCommand $command, TaskResolverInterface $resolvedBy): void
    {
        if ($this->command !== null) {
            throw new \Exception('Already resolved');
        }

        $this->command = $command;
        $this->resolver = $resolvedBy;
    }

    public function isResolved(): bool
    {
        return $this->command !== null;
    }

    /**
     * @return TaskResolverInterface|null
     */
    public function getResolver(): ?TaskResolverInterface
    {
        return $this->resolver;
    }

    /**
     * @return BaseCommand|null
     */
    public function getCommand(): ?BaseCommand
    {
        return $this->command;
    }

    public function setResult(CommandHandlingResultInterface $result): void
    {
        $this->result = $result;
    }

    public function getResult(): ?CommandHandlingResultInterface
    {
        return $this->result;
    }
}
