<?php

namespace hiqdev\recon\core\Service;

use hiqdev\recon\core\Command\IncomingTask;
use hiqdev\recon\core\Event\FailedTaskEvent;
use hiqdev\recon\core\Event\TaskEvent;
use hiqdev\recon\core\Exception\ReconException;
use hiqdev\recon\core\Exception\SkipTaskException;
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
    public const EVENT_RESOLVE_TASK = 'TASK_RESOLVE';

    public const EVENT_TASK_ACQUIRED = 'TASK_START';
    public const EVENT_TASK_DONE     = 'TASK_DONE';
    public const EVENT_TASK_FAILED   = 'TASK_FAILED';
    public const EVENT_TASK_DELAYED  = 'TASK_DELAYED';

    /**
     * @var EmitterInterface
     */
    private $emitter;
    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(EmitterInterface $emitter, LoggerInterface $logger)
    {
        $this->emitter = $emitter;
        $this->log = $logger;
    }

    public function run(IncomingTask $task)
    {
        $this->emitter->emit(TaskEvent::create(self::EVENT_RESOLVE_TASK, $task));

        if (!$task->isResolved()) {
            $this->log->error('Task was not resolved to a command', [
                'id' => $task->id,
                'name' => $task->name,
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
        } catch (ReconException $e) {
            $this->log->error('An error occurred', [
                'task_id' => $task->id,
                'reason' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);

            $task->setResult($e->getMessage());
            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task)
                               ->setException($e)
            );
        } catch (Throwable $e) {
            $this->log->critical('An unknown error occurred', [
                'task_id' => $task->id,
                'reason' => $e->getMessage(),
                'stacktrace' => $e->getTraceAsString()
            ]);

            $this->emitter->emit(
                FailedTaskEvent::create(self::EVENT_TASK_FAILED, $task)
                               ->setException($e)
            );
        }
    }
}
