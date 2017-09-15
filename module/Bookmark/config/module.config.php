<?php

namespace Bookmark;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'api-restaurant-bookmark' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/bookmark/restaurantbookmark',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\RestaurantBookmarkController::class,
                    ],
                ],
            ],
            'api-food-bookmark' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/bookmark/foodbookmark',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\FoodBookmarkController::class,
                    ],
                ],
            ],
        ],
    ],
];
