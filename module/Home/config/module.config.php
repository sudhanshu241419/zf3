<?php

namespace Home;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'api-banner' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/home/banner[/:id]',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\BannersController::class
                    ],
                ],
            ],
            'api-campaigns' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/home/campaigns',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CampaignsController::class
                    ],
                ],
            ],
            'api-update-app' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/home/updateapp',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\FourceUpdateAppController::class
                    ],
                ],
            ],
            'api-home-location' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/home/location',
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\LocationController::class
                    ],
                ],
            ],
        ],
    ],
];
