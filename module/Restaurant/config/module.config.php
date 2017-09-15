<?php

namespace Restaurant;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'api-restaurant-overview' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/restaurant/overview[/:id]',
                    'defaults' => [
                        'controller' => Controller\RestaurantOverviewController::class,
                    ],
                ],
            ],
            'api-restaurant-menu' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/restaurant/menu[/:id]',
                    'defaults' => [
                        'controller' => Controller\RestaurantMenuController::class,
                    ],
                ],
            ],
            'api-restaurant-menu-addons' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/restaurant/menu/addons[/:id]',
                    'defaults' => [
                        'controller' => Controller\RestaurantMenuAddonsController::class,
                    ],
                ],
            ],
            'api-restaurant-story' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/restaurant/story[/:id]',
                    'defaults' => [
                        'controller' => Controller\RestaurantStoryController::class,
                    ],
                ],
            ],
            'api-restaurant-review' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/restaurant/review[/:id]',
                    'defaults' => [
                        'controller' => Controller\RestaurantReviewController::class,
                    ],
                ],
            ],
        ],
    ],
];
