<?php

namespace hiqdev\recon\core\Service;

use hiqdev\recon\core\Command\IncomingTask;
use hiqdev\recon\core\Event\FailedTaskEvent;
use hiqdev\recon\core\Event\TaskEvent;
use hiqdev\recon\core\Exception\DeferTaskException;
use hiqdev\recon\core\Exception\ReconException;
use hiqdev\recon\core\Exception\SkipTaskException;
use hiqdev\yii2\autobus\components\CommandBusInterface;
use League\Event\EmitterInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TaskLifecycle
{
    /**
     * Each handler MUST check, that task is not resolved yet `!$task->isResolved()`
     * and resolve, if this task resolving is its responsibility.
     */
    public const EVENT_RESOLVE_TASK  = __CLASS__ . '::EVENT_TASK_RESOLVE';
    public const EVENT_TASK_ACQUIRED = __CLASS__ . '::EVENT_TASK_START';
    public const EVENT_TASK_DONE     = __CLASS__ . '::EVENT_TASK_DONE';
    public const EVENT_TASK_FAILED   = __CLASS__ . '::EVENT_TASK_FAILED';
    public const EVENT_TASK_DEFERRED = __CLASS__ . '::EVENT_TASK_DEFERRED';

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

    public function run(IncomingTask $task)
    {
        $this->emitter->emit(TaskEvent::create(self::EVENT_RESOLVE_TASK, $task));

        if (!$task->isResolved()) {
            $this->log->error('Task was not resolved to a command', [
                'id' => $task->id,
                'type' => $task->type,
            ]);

            throw new RuntimeException('Task was not resolved');
        }

        try {
            $this->emitter->emit(TaskEvent::create(self::EVENT_TASK_ACQUIRED, $task));

            $result = $this->bus->handle($task->getCommand());
            $task->setResult($result);

            $this->emitter->emit(TaskEvent::create(self::EVENT_TASK_DONE, $task));

            return $result;
        } catch (SkipTaskException $e) {
            $this->log->debug('Task was skipped', [
                'task_id' => $task->id,
                'reason' => $e->getMessage()
            ]);
            return null;
        } catch (DeferTaskException $e) {
            $this->log->debug('Task was deferred', [
                'task_id' => $task->id,
                'reason' => $e->getMessage()
            ]);

            $task->setResult($e->getMessage());
            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_DEFERRED, $task)->setException($e)
            );
        } catch (ReconException $e) {
            $this->log->error('An error occurred', [
                'task_id' => $task->id,
                'reason' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);

            $task->setResult($e->getMessage());
            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task)->setException($e)
            );
        } catch (Throwable $e) {
            $this->log->critical('An unknown error occurred', [
                'task_id' => $task->id,
                'reason' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);

            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task)->setException($e)
            );
        }
    }
}
