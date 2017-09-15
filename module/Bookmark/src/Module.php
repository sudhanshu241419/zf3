<?php

namespace Bookmark;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface {

    public function getConfig() {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig() {
        return [
            'factories' => [
                
            ],
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\RestaurantBookmarkController::class => function($container) {
                    return new Controller\RestaurantBookmarkController();
                },
                Controller\FoodBookmarkController::class => function($container) {
                    return new Controller\FoodBookmarkController();
                }
            ],
        ];
    }

}
