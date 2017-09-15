<?php
namespace Search;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'web-search' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/wapi/search',
                    'defaults' => [
                        'controller' => Controller\SearchController::class,
                    ],
                ],
            ],
            'mobile-search' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/search',
                    'defaults' => [
                        'controller' => Controller\MobSearchController::class,
                    ],
                ],
            ],
            'type-of-place' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/typeofplace',
                    'defaults' => [
                        'controller' => Controller\TypeOfPlaceController::class,
                    ],
                ],
            ],
            'search-user-deals' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/api/search/userdeals',
                    'defaults' => [
                        'controller' => Controller\MobSearchController::class,
                    ],
                ],
            ],
        ],
    ],
];