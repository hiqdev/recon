<?php

namespace hiqdev\recon\core\Service;

use GuzzleHttp\Client;
use hiqdev\recon\core\Command\IncomingTask;
use hiqdev\recon\core\Event\FailedTaskEvent;
use hiqdev\recon\core\Event\TaskEvent;
use hiqdev\recon\core\Exception\DeferTaskException;
use hiqdev\recon\core\Model\Service;
use hiqdev\recon\core\Model\ServiceAwareInterface;
use InvalidArgumentException;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Psr\Log\LoggerInterface;
use yii\mutex\FileMutex;

/**
 * Class TaskServiceMutex prevents execution of multiple concurrent requests on the
 * same service to prevent configuration overlapping.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class TaskServiceMutex extends AbstractListener
{
    private const METHOD_MAP = [
        TaskLifecycle::EVENT_TASK_ACQUIRED => 'acquireLock',
        TaskLifecycle::EVENT_TASK_DONE => 'releaseLock',
        TaskLifecycle::EVENT_TASK_FAILED => 'releaseLock',
    ];
    /**
     * @var LoggerInterface
     */
    private $log;
    /**
     * @var FileMutex
     */
    private $mutex;

    public function __construct(FileMutex $mutex, LoggerInterface $log)
    {
        $this->log = $log;
        $this->mutex = $mutex;
    }

    /**
     * Handle an event.
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function handle(EventInterface $event): void
    {
        if (!$event instanceof TaskEvent) {
            return;
        }

        $task = $event->getTarget();
        $methodName = self::METHOD_MAP[$event->getName()] ?? null;
        if ($methodName === null || !$task->getResolver() instanceof TaskResolverInterface) {
            return;
        }

        $this->{$methodName}($event);
    }

    private function acquireLock(TaskEvent $event): void
    {
        $command = $event->getTarget()->getCommand();
        if (!$command instanceof ServiceAwareInterface) {
            return;
        }
        $service = $command->getService();

        if (!$this->mutex->acquire($this->lockName($service), 2)) {
            throw new DeferTaskException('Could not acquire lock for service ' . $service->id);
        }
    }

    private function releaseLock(TaskEvent $event): void
    {
        $command = $event->getTarget()->getCommand();
        if (!$command instanceof ServiceAwareInterface) {
            return;
        }
        $service = $command->getService();

        $this->mutex->release($this->lockName($service));
    }

    private function lockName(Service $service): string
    {
        return 'service-' . $service->id;
    }
}
