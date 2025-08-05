<?php

declare(strict_types=1);

namespace Application;

use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;
use Laminas\ServiceManager\Factory\InvokableFactory;

return [
    'validators' => [
        'hostname' => [
            'allow' => \Laminas\Validator\Hostname::ALLOW_ALL,
            'useIdnCheck' => false,
            'useTldCheck' => false,
        ],
    ],

    'router' => [
        'routes' => [
            // ✅ Parent "career" route
            'career' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/career',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'test' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/test',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'test',
                            ],
                        ],
                    ],
                    'submit' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/submit',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'submit',
                            ],
                        ],
                    ],
                    'analyze-dataset' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/analyze-dataset',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'analyzeDataset',
                            ],
                        ],
                    ],
                    // ✅ NEW: Result page
                    'result' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/result',
                            'defaults' => [
                                'controller' => Controller\IndexController::class,
                                'action' => 'result',
                            ],
                        ],
                    ],
                ],
            ],

            // ✅ Home route
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],

            // ✅ Application fallback route
            'application' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'application/index/analyze-dataset' => __DIR__ . '/../view/application/index/analyze-dataset.phtml',
            'application/index/result' => __DIR__ . '/../view/application/index/result.phtml', // ✅ NEW TEMPLATE
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],

    'host_validator' => [
        'allow_empty' => false,
        'whitelist' => [
            'localhost',
            '127.0.0.1',
            'localhost:8080',
        ],
    ],
];
