<?php

$params = $params ?? [];

return [
    'container' => [
        'singletons' => [
            \hiqdev\recon\core\Service\AmqpBindingsProvider::class => [
                '__class' => \hiqdev\recon\core\Service\AmqpBindingsProvider::class,
            ],

            /// Connectivity
            \PhpAmqpLib\Connection\AMQPStreamConnection::class => [
                '__class' => \PhpAmqpLib\Connection\AMQPLazyConnection::class,
                '__construct()' => [
                    $params['amqp.host'],
                    $params['amqp.port'],
                    $params['amqp.user'],
                    $params['amqp.password']
                ],
            ],

            'recon.queue' => [
                '__class' => \hiqdev\yii2\autobus\components\SingleCommandAutoBus::class,
                '__construct()' => [
                    [
                        '__class' => \hiqdev\recon\core\Command\IncomingTask::class,
                    ]
                ],
            ],

            \hiqdev\recon\core\Service\TaskLifecycle::class => [
                '__class' => \hiqdev\recon\core\Service\TaskLifecycle::class,
                '__construct()' => [
                    \hiqdev\yii\compat\yii::referenceTo('task-lifecycle-emitter'),
                ],
            ],

            /// Request handling
            'task-lifecycle-emitter' => [
                '__class' => \hiapi\event\ConfigurableEmitter::class,
                'listeners' => [
// Example: add your listener for events, described in TaskLifecycle class
//                    [
//                        'event' => \hiqdev\recon\core\Service\TaskLifecycle::EVENT_RESOLVE_TASK,
//                        'listener' => \hiqdev\recon\core\Service\TaskResolver::class,
//                    ],
                ],
            ],
        ]
    ]
];
