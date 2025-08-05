<?php
return [
    'db' => [
        'driver'   => 'Pdo_Mysql',
        'hostname' => '127.0.0.1',
        'database' => 'career_recommendation',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8',
    ],
    'service_manager' => [
        'factories' => [
            Laminas\Db\Adapter\Adapter::class => Laminas\Db\Adapter\AdapterServiceFactory::class,
        ],
    ],
];
