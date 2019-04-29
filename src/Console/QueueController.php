<?php

namespace hiqdev\recon\core\Console;

use hiqdev\recon\core\Service\AmqpBindingsProvider;
use hiqdev\yii2\autobus\components\AutoBusFactoryInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;
use yii\base\Module;

class QueueController extends \hiapi\console\QueueController
{
    /**
     * @var AmqpBindingsProvider
     */
    private $bindingsProvider;

    public function __construct(
        $id,
        Module $module,
        AMQPStreamConnection $amqp,
        LoggerInterface $logger,
        AutoBusFactoryInterface $busFactory,
        AmqpBindingsProvider $bindingsProvider,
        array $config = []
    ) {
        $this->bindingsProvider = $bindingsProvider;

        parent::__construct($id, $module, $amqp, $logger, $busFactory, $config);
    }

    public function init()
    {
        parent::init();

        $this->bindingsProvider->bind($this->amqp->channel());
    }
}
