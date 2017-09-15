<?php

namespace Restaurant;

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
                Model\RestaurantCalendar::class => function ($container) {
                    $tableGateway = $container->get('RestaurantCalendarGateway');
                    return new Model\RestaurantCalendar($tableGateway);
                },
                'RestaurantCalendarGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurant_calendars', $dbAdapter, null, $resultSetPrototype);
                },
                Model\RestaurantServer::class => function($container) {
                    $tableGateway = $container->get('RestaurantServerGateway');
                    return new Model\RestaurantServer($tableGateway);
                },
                'RestaurantServerGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurant_servers', $dbAdapter, null, $resultSetPrototype);
                },
                Model\Tags::class => function($container) {
                    $tableGateway = $container->get('TagsGateway');
                    return new Model\Tags($tableGateway);
                },
                'TagsGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('tags', $dbAdapter, null, $resultSetPrototype);
                },
                Model\Restaurant::class => function($container) {
                    $tableGateway = $container->get('RestaurantGateway');
                    return new Model\Restaurant($tableGateway);
                },
                'RestaurantGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurants', $dbAdapter, null, $resultSetPrototype);
                },
                Model\RestaurantDealsCoupons::class => function($container) {
                    $tableGateway = $container->get('RestaurantDealsCouponsGateway');
                    return new Model\RestaurantDealsCoupons($tableGateway);
                },
                'RestaurantDealsCouponsGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurant_deals_coupons', $dbAdapter, null, $resultSetPrototype);
                },
                Model\Promocodes::class => function($container) {
                    $tableGateway = $container->get('PromocodesGateway');
                    return new Model\Promocodes($tableGateway);
                },
                'PromocodesGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('promocodes', $dbAdapter, null, $resultSetPrototype);
                },
                \Restaurant\Model\RestaurantBookmark::class => function($container) {
                    $tableGateway = $container->get('RestaurantBookmarkGateway');
                    return new \Restaurant\Model\RestaurantBookmark($tableGateway);
                },
                'RestaurantBookmarkGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurant_bookmarks', $dbAdapter, null, $resultSetPrototype);
                },
                \Restaurant\Model\RestaurantReview::class => function($container) {
                    $tableGateway = $container->get('RestaurantReviewGateway');
                    return new \Restaurant\Model\RestaurantReview($tableGateway);
                },
                'RestaurantReviewGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurant_reviews', $dbAdapter, null, $resultSetPrototype);
                },
                \Restaurant\Model\MenuBookmark::class => function($container) {
                    $tableGateway = $container->get('MenuBookmarkGateway');
                    return new \Restaurant\Model\MenuBookmark($tableGateway);
                },
                'MenuBookmarkGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('menu_bookmarks', $dbAdapter, null, $resultSetPrototype);
                },
                \Restaurant\Model\RestaurantStory::class => function($container) {
                    $tableGateway = $container->get('RestaurantStoryGateway');
                    return new \Restaurant\Model\RestaurantStory($tableGateway);
                },
                'RestaurantStoryGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('restaurant_stories', $dbAdapter, null, $resultSetPrototype);
                },
                \Restaurant\Model\Menu::class => function($container) {
                    $tableGateway = $container->get('MenuGateway');
                    return new \Restaurant\Model\Menu($tableGateway);
                },
                'MenuGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('menus', $dbAdapter, null, $resultSetPrototype);
                },
                \Restaurant\Model\MenuAddons::class => function($container) {
                    $tableGateway = $container->get('MenuAddonsGateway');
                    return new \Restaurant\Model\MenuAddons($tableGateway);
                },
                'MenuAddonsGateway' => function($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('menu_addons', $dbAdapter, null, $resultSetPrototype);
                }
            ],
        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\IndexController::class => function() {
                    return new Controller\IndexController();
                },
                Controller\RestaurantBookmarkController::class => function($container) {
                    return new Controller\RestaurantBookmarkController();
                },
                OrderFunctions::class=>function(){
                    return new OrderFunctions();
                },
                Controller\RestaurantOverviewController::class => function() {
                    return new Controller\RestaurantOverviewController();
                },
                Controller\RestaurantMenuController::class => function() {
                    return new Controller\RestaurantMenuController();
                },
                Controller\RestaurantMenuAddonsController::class => function() {
                    return new Controller\RestaurantMenuAddonsController();
                },
                Controller\RestaurantReviewController::class => function() {
                    return new Controller\RestaurantReviewController();
                },
                Controller\RestaurantStoryController::class => function() {
                    return new Controller\RestaurantStoryController();
                },
                Controller\UserFeedbackController::class => function() {
                    return new Controller\UserFeedbackController();
                }
                
            ],
        ];
    }

}
