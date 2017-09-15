<?php

namespace City;

use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'city-list' => [
                'type'    => Segment::class,
                'options' => [
                    'route'       => 'api/city[/:id]',
                    'defaults'    => [
                        'controller' => Controller\CityController::class,
                    ],
                ],
            ],
        ],
    ],
];
