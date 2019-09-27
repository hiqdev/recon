<?php

namespace hiqdev\recon\core\Service;

use hiqdev\recon\core\Command\IncomingTask;
use hiqdev\recon\core\Event\FailedTaskEvent;
use hiqdev\recon\core\Event\TaskEvent;
use hiqdev\recon\core\Exception\DeferTaskException;
use hiqdev\recon\core\Exception\ReconException;
use hiqdev\recon\core\Exception\SkipTaskException;
use hiqdev\recon\core\Model\CommandHandlingResultInterface;
use hiqdev\recon\core\Model\SimpleCommandHandlingResult;
use hiqdev\yii2\autobus\components\CommandBusInterface;
use League\Event\EmitterInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class TaskLifecycle
{
    /**
     * Each handler MUST check, that task is not resolved yet `!$task->isResolved()`
     * and resolve, if this task resolving is its responsibility.
     */
    public const EVENT_RESOLVE_TASK   = __CLASS__ . '::EVENT_TASK_RESOLVE';
    public const EVENT_TASK_ACQUIRED  = __CLASS__ . '::EVENT_TASK_START';
    public const EVENT_TASK_CONTINUES = __CLASS__ . '::EVENT_TASK_CONTINUES';
    public const EVENT_TASK_DONE      = __CLASS__ . '::EVENT_TASK_DONE';
    public const EVENT_TASK_FAILED    = __CLASS__ . '::EVENT_TASK_FAILED';
    public const EVENT_TASK_DEFERRED  = __CLASS__ . '::EVENT_TASK_DEFERRED';

    /**
     * @var EmitterInterface
     */
    private $emitter;
    /**
     * @var LoggerInterface
     */
    private $log;
    /**
     * @var CommandBusInterface
     */
    private $bus;

    public function __construct(EmitterInterface $emitter, CommandBusInterface $bus, LoggerInterface $logger)
    {
        $this->emitter = $emitter;
        $this->log = $logger;
        $this->bus = $bus;
    }

    /**
     * @param IncomingTask $task
     * @throws SkipTaskException when task could not be resolved to command
     */
    private function ensureTaskIsResolved(IncomingTask $task): void
    {
        if (!$task->isResolved()) {
            throw new SkipTaskException('Task was not resolved');
        }
    }

    private function handleTask(IncomingTask $task): void
    {
        $result = $this->bus->handle($task->getCommand());

        if ($result instanceof CommandHandlingResultInterface) {
            $handlingResult = $result;
        } elseif (is_string($result)) {
            $handlingResult = new SimpleCommandHandlingResult($task->getCommand());
            $handlingResult->stdout = $result;
        } elseif ($result === null) {
            $handlingResult = new SimpleCommandHandlingResult($task->getCommand());
        } else {
            $this->log->warning('Command handler returned unexpected result', [
                'task' => $task->getCommand(),
                'result' => $result
            ]);
            $handlingResult = new SimpleCommandHandlingResult($task->getCommand());
        }

        $task->setResult($handlingResult);

        if ($handlingResult->isSuccess() === true) {
            $this->emitter->emit(TaskEvent::create(self::EVENT_TASK_DONE, $task));
        } elseif ($handlingResult->isSuccess() === false) {
            $this->emitter->emit(FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task));
        } elseif ($handlingResult->isSuccess() === null) {
            $this->emitter->emit(TaskEvent::create(self::EVENT_TASK_CONTINUES, $task));
        }
    }

    public function run(IncomingTask $task): void
    {
        try {
            $this->emitter->emit(TaskEvent::create(self::EVENT_RESOLVE_TASK, $task));
            $this->ensureTaskIsResolved($task);
            $this->emitter->emit(TaskEvent::create(self::EVENT_TASK_ACQUIRED, $task));
            $this->handleTask($task);
        } catch (SkipTaskException $e) {
            $this->log->debug('Task was skipped', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'reason' => $e->getMessage()
            ]);
            return;
        } catch (DeferTaskException $e) {
            $this->log->debug('Task was deferred', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'reason' => $e->getMessage()
            ]);

            $task->setResult($this->buildHandlingResultOutOfException($task, $e));
            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_DEFERRED, $task)->setException($e)
            );
        } catch (ReconException $e) {
            $this->log->error('An error occurred', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'reason' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);

            $task->setResult($this->buildHandlingResultOutOfException($task, $e));
            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task)->setException($e)
            );
        } catch (Throwable $e) {
            $this->log->critical('An unknown error occurred', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'reason' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);

            $task->setResult($this->buildHandlingResultOutOfException($task, $e));
            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task)->setException($e)
            );
        }
    }

    private function buildHandlingResultOutOfException(IncomingTask $task, \Throwable $e): CommandHandlingResultInterface
    {
        $result = new SimpleCommandHandlingResult($task->getCommand());
        $result->isSuccess = false;
        $result->stderr = $e->getMessage();

        return $result;
    }
}
