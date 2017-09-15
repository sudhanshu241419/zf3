<?php

namespace City;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    public function getServiceConfig()
    {
        return [
            'factories' => [
                Model\City::class        => function ($container) {
                    $tableGateway = $container->get('Model\CityGateway');
                    return new Model\City($tableGateway);
                },
                'Model\CityGateway' => function ($container) {
                    $dbAdapter          = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('cities', $dbAdapter, null, $resultSetPrototype);
                },
            ],
        ];
    }

    public function getControllerConfig()
    {
        return [
            'factories' => [
                Controller\CityController::class => function ($container) {
                    return new Controller\CityController();
                },
            ],
        ];
    }
}
