<?php

namespace Auth;

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
                Model\Auth::class => function($container) {
                    $tableGateway = $container->get(Model\AuthGateway::class);                   
                    return new Model\Auth($tableGateway);
                },
                Model\AuthGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('auth', $dbAdapter, null,$resultSetPrototype);
                }, 
                Model\UserSession::class => function($container) {
                    $tableGateway = $container->get(Model\UserSessionGateway::class);                   
                    return new Model\UserSession($tableGateway);
                },
                Model\UserSessionGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('auth', $dbAdapter, null,$resultSetPrototype);
                }, 
               
            ],
                        
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\AuthController::class => function($container) {
                    return new Controller\AuthController();
                },
               
            ],
        ];
    }
}
