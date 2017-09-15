<?php
namespace Search;

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
                    \Search\Solr\Synonyms::class => function ($container){
                        return new \Search\Solr\Synonyms();
                    },
                    \Search\Common\Utility::class => function ($container){
                        return new \Search\Common\Utility();
                    },
                    \MUtility\MongoLogger::class => function($container){
                        return new \MUtility\MongoLogger();
                    },
                    \Search\Solr\SearchUrlsMobile::class => function($container){
                        return new \Search\Solr\SearchUrlsMobile();
                    },
                    \City\Model\City::class => function ($container) {
                        $tableGateway = $container->get('Model\CityTableGateway');
                        return new \City\Model\City($tableGateway);
                    },
                    'Model\CityTableGateway' => function ($container) {
                        $dbAdapter          = $container->get(AdapterInterface::class);
                        $resultSetPrototype = new ResultSet();
                        return new TableGateway('cities', $dbAdapter, null, $resultSetPrototype);
                    },
                    \User\Model\UserReview::class => function($container){
                        $tableGateway = $container->get('UserReviewGateway');
                        return new \User\Model\UserReview($tableGateway);
                    },
                    'UserReviewGateway' => function($container){
                        $dbAdapter          =   $container->get(AdapterInterface::class);
                        $resultSetPrototype =   new ResultSet();
                        return new TableGateway('user_reviews',$dbAdapter,null,$resultSetPrototype);
                    },
                    \User\Model\UserTip::class => function($container){
                        $tableGateway = $container->get('UserTipsGateway');
                        return new \User\Model\UserTip($tableGateway);
                    },
                    'UserTipsGateway' => function($container){
                        $dbAdapter              =   $container->get(AdapterInterface::class);
                        $resultSetPrototype     =   new ResultSet();
                        return new TableGateway('user_tips',$dbAdapter,null,$resultSetPrototype);
                    },
                    Solr\PickAnAreaMobile::class => function($container){
                        return new Solr\PickAnAreaMobile();
                    },
                    \Restaurant\Model\Feature::class => function($container){
                        $tableGateway = $container->get('FeatureGateway');
                        return new \Restaurant\Model\Feature($tableGateway);
                    },
                    'FeatureGateway'            =>  function($container){
                        $dbAdapter              =   $container->get(AdapterInterface::class);
                        $resultSetPrototype     =   new ResultSet();
                        return new TableGateway('features',$dbAdapter,null,$resultSetPrototype);
                    },
                    \User\Model\UserFriends::class => function($container){
                        $tableGateway = $container->get('UserFriendsGateway');
                        return new \User\Model\UserFriends($tableGateway);
                    },
                    'UserFriendsGateway'        =>  function($container){
                        $dbAdapter              =   $container->get(AdapterInterface::class);
                        $resultSetPrototype     =   new ResultSet();
                        return new TableGateway('user_friends',$dbAdapter,null,$resultSetPrototype);
                    },
                    \User\Model\UserDeals::class => function($container){
                        $tableGateway = $container->get('UserDealsGateway');
                        return new \User\Model\UserDeals($tableGateway);
                    },
                    'UserDealsGateway'          =>  function($container){
                        $dbAdapter              =   $container->get(AdapterInterface::class);
                        $resultSetPrototype     =   new ResultSet();
                        return new TableGateway('user_deals',$dbAdapter,null,$resultSetPrototype);
                    }
            ],
        ];
    }

    public function getControllerConfig() 
    {
        return [
            'factories' => [
                Controller\SearchController::class => function($container) {
                    return new Controller\SearchController();
                },
                Controller\MobSearchController::class => function($container) {
                    return new Controller\MobSearchController();
                },
                Controller\TypeOfPlaceController::class => function($container) {
                    return new Controller\TypeOfPlaceController();
                }         
            ],
        ];
    }
}