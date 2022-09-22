<?php

declare(strict_types=1);

use function Imi\env;

return [
    // 项目根命名空间
    'namespace'    => 'app',

    // 配置文件
    'configs'    => [
        'beans'        => __DIR__ . '/beans.php',
    ],

    // 组件命名空间
    'components'    => [
        'Migration' => 'Imi\Migration',
    ],

    // 数据库配置
    'db'    => [
        // 默认连接池名
        'defaultPool'    => 'maindb',
        'connections'    => [
            'maindb'         => [
                'host'              => env('MYSQL_SERVER_HOST', '127.0.0.1'),
                'port'              => env('MYSQL_SERVER_PORT', 3306),
                'username'          => env('MYSQL_SERVER_USERNAME', 'root'),
                'password'          => env('MYSQL_SERVER_PASSWORD', 'root'),
                'database'          => 'db_imi_migration_test',
                'charset'           => 'utf8mb4',
                'heartbeatInterval' => 30,
            ],
        ],
    ],

    // 日志配置
    'logger' => [
        'channels' => [
            'imi' => [
                'handlers' => [
                    [
                        'class'     => \Imi\Log\Handler\ConsoleHandler::class,
                        'formatter' => [
                            'class'     => \Imi\Log\Formatter\ConsoleLineFormatter::class,
                            'construct' => [
                                'format'                     => null,
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                    [
                        'class'     => \Monolog\Handler\RotatingFileHandler::class,
                        'construct' => [
                            'filename' => \dirname(__DIR__) . '/.runtime/logs/log.log',
                        ],
                        'formatter' => [
                            'class'     => \Monolog\Formatter\LineFormatter::class,
                            'construct' => [
                                'dateFormat'                 => 'Y-m-d H:i:s',
                                'allowInlineLineBreaks'      => true,
                                'ignoreEmptyContextAndExtra' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
