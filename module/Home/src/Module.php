<?php

namespace Home;

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
                Model\State::class => function($container) {
                    $tableGateway = $container->get('StateGateway');
                    return new Model\State($tableGateway);
                },
                'StateGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('states', $dbAdapter, null, $resultSetPrototype);
                },
                Model\Location::class => function($container) {
                    $tableGateway = $container->get('LocationGateway');
                    return new Model\Location($tableGateway);
                },
                'LocationGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_orders', $dbAdapter, null, $resultSetPrototype);
                }
            ],
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\BannersController::class => function() {
                    return new Controller\BannersController();
                },
                Controller\CampaignsController::class => function() {
                    return new Controller\CampaignsController();
                },
                Controller\FourceUpdateAppController::class => function() {
                    return new Controller\FourceUpdateAppController();
                },
                Controller\LocationController::class => function() {
                    return new Controller\LocationController();
                }
            ],
        ];
    }

}
