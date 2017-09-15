<?php

namespace User;

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
                Model\User::class => function($container) {
                    $tableGateway = $container->get(Model\UserGateway::class);
                    return new Model\User($tableGateway);
                },
                Model\UserGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserAccount::class => function($container) {
                    $tableGateway = $container->get(Model\UserAccountGateway::class);
                    return new Model\UserAccount($tableGateway);
                },
                Model\UserAccountGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_account', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserNotification::class => function($container) {
                    $tableGateway = $container->get(Model\UserNotificationGateway::class);
                    return new Model\UserNotification($tableGateway);
                },
                Model\UserNotificationGateway::class => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('pubnub_notification', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserPromoCodes::class => function($container) {
                    $tableGateway = $container->get('UserPromoCodesGateway');
                    return new Model\UserPromoCodes($tableGateway);
                },
                'UserPromoCodesGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_promocodes', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserReservation::class => function($container) {
                    $tableGateway = $container->get('UserReservationGateway');
                    return new Model\UserReservation($tableGateway);
                },
                'UserReservationGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_reservations', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserOrder::class => function($container) {
                    $tableGateway = $container->get('UserOrderGateway');
                    return new Model\UserOrder($tableGateway);
                },
                'UserOrderGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_orders', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserSetting::class => function($container) {
                    $tableGateway = $container->get('UserSettingGateway');
                    return new Model\UserSetting($tableGateway);
                },
                'UserSettingGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_settings', $dbAdapter, null, $resultSetPrototype);
                },
                Model\PointSourceDetails::class => function($container) {
                    $tableGateway = $container->get('PointSourceDetailsGateway');
                    return new Model\PointSourceDetails($tableGateway);
                },
                'PointSourceDetailsGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('point_source_detail', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserPoint::class => function($container) {
                    $tableGateway = $container->get('UserPointGateway');
                    return new Model\UserPoint($tableGateway);
                },
                'UserPointGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_points', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserActionSettings::class => function($container) {
                    $tableGateway = $container->get('UserActionSettingsGateway');
                    return new Model\UserActionSettings($tableGateway);
                },
                'UserActionSettingsGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_action_settings', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserAddress::class => function($container) {
                    $tableGateway = $container->get('UserAddressGateway');
                    return new Model\UserAddress($tableGateway);
                },
                'UserAddressGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_addresses', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserPoints::class => function($container) {
                    $tableGateway = $container->get('UserPointsGateway');
                    return new Model\UserPoints($tableGateway);
                },
                'UserPointsGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_points', $dbAdapter, null, $resultSetPrototype);
                },
                Model\ActivityFeedType::class => function($container) {
                    $tableGateway = $container->get('ActivityFeedTypeGateway');
                    return new Model\ActivityFeedType($tableGateway);
                },
                'ActivityFeedTypeGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('activity_feed_type', $dbAdapter, null, $resultSetPrototype);
                },
                Model\ActivityFeed::class => function($container) {
                    $tableGateway = $container->get('ActivityFeedGateway');
                    return new Model\ActivityFeed($tableGateway);
                },
                'ActivityFeedGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('activity_feed', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserInvitation::class => function($container) {
                    $tableGateway = $container->get('UserInvitationGateway');
                    return new Model\UserInvitation($tableGateway);
                },
                'UserInvitationGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_reservation_invitation', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserReservation::class => function($container) {
                    $tableGateway = $container->get('UserReservationGateway');
                    return new Model\UserReservation($tableGateway);
                },
                'UserReservationGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_reservations', $dbAdapter, null, $resultSetPrototype);
                },
                Model\Avatar::class => function($container) {
                    $tableGateway = $container->get('AvatarGateway');
                    return new Model\Avatar($tableGateway);
                },
                'AvatarGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('avatar', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserAvatar::class => function($container) {
                    $tableGateway = $container->get('UserAvatarGateway');
                    return new Model\UserAvatar($tableGateway);
                },
                'UserAvatarGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_avatar', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserRestaurantImage::class => function($container) {
                    $tableGateway = $container->get('UserRestaurantImageGateway');
                    return new Model\UserRestaurantImage($tableGateway);
                },
                'UserRestaurantImageGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_restaurant_image', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserEatingHabits::class => function($container) {
                    $tableGateway = $container->get('UserEatingHabitsGateway');
                    return new Model\UserEatingHabits($tableGateway);
                },
                'UserEatingHabitsGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_eating_habits', $dbAdapter, null, $resultSetPrototype);
                },
                Model\FeedComment::class => function($container) {
                    $tableGateway = $container->get('FeedCommentGateway');
                    return new Model\FeedComment($tableGateway);
                },
                'FeedCommentGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('feed_comment', $dbAdapter, null, $resultSetPrototype);
                },
                Model\FeedBookmark::class => function($container) {
                    $tableGateway = $container->get('FeedBookmarkGateway');
                    return new Model\FeedBookmark($tableGateway);
                },
                'FeedBookmarkGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('feed_bookmark', $dbAdapter, null, $resultSetPrototype);
                },

                Model\UserOrderDetail::class => function($container) {
                    $tableGateway = $container->get('UserOrderDetailGateway');
                    return new Model\UserOrderDetail($tableGateway);
                },
                'UserOrderDetailGateway'=> function ($container){
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_order_details', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserOrderAddons::class => function($container) {
                    $tableGateway = $container->get('UserOrderAddonsGateway');
                    return new Model\UserOrderAddons($tableGateway);
                },
                'UserOrderAddonsGateway'=> function ($container){
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_order_addons', $dbAdapter, null, $resultSetPrototype);
                },

                Model\UserCheckin::class => function($container) {
                    $tableGateway = $container->get('UserCheckinGateway');
                    return new Model\UserCheckin($tableGateway);
                },
                'UserCheckinGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_checkin', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserRestaurantImage::class => function($container) {
                    $tableGateway = $container->get('UserRestaurantImageGateway');
                    return new Model\UserRestaurantImage($tableGateway);
                },
                'UserRestaurantImageGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_restaurant_image', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserReviewImage::class => function($container) {
                    $tableGateway = $container->get('UserReviewImageGateway');
                    return new Model\UserReviewImage($tableGateway);
                },
                'UserReviewImageGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_review_images', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserFeedback::class => function($container) {
                    $tableGateway = $container->get('UserFeedbackGateway');
                    return new Model\UserFeedback($tableGateway);
                },
                'UserFeedbackGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_feedback', $dbAdapter, null, $resultSetPrototype);
                },
                Model\UserMenuReview::class => function($container) {
                    $tableGateway = $container->get('UserMenuReviewGateway');
                    return new Model\UserMenuReview($tableGateway);
                },
                'UserMenuReviewGateway' => function ($container) {
                    $dbAdapter = $container->get(AdapterInterface::class);
                    $resultSetPrototype = new ResultSet();
                    return new TableGateway('user_menu_reviews', $dbAdapter, null, $resultSetPrototype);
                },
            ],

        ];
    }

    public function getControllerConfig() {
        return [
            'factories' => [
                Controller\UserDetailsController::class => function() {
                    return new Controller\UserDetailsController();
                },
                Controller\LoginController::class => function() {
                    return new Controller\LoginController();
                },
                Controller\UserPromoCodesController::class => function() {
                    return new Controller\UserPromoCodesController();
                },
                Controller\UserReferralCodeController::class => function() {
                    return new Controller\UserReferralCodeController();
                },
                Controller\UserCurrentNotificationController::class => function() {
                    return new Controller\UserCurrentNotificationController();
                },
                Controller\LocationController::class => function() {
                    return new Controller\LocationController();
                },
                Controller\UserAddressController::class => function() {
                    return new Controller\UserAddressController();
                },
                Controller\UserPointsController::class => function() {
                    return new Controller\UserPointsController();
                },
                Controller\MyMuncherController::class => function() {
                    return new Controller\MyMuncherController();
                },
                Controller\UserFriendController::class => function() {
                    return new Controller\UserFriendController();
                },
                Controller\LogoutController::class => function($container) {
                    return new Controller\LogoutController();
                },
                Form\LoginForm::class => function () {
                    return new Form\LoginForm();
                },
                Functions\UserFunctions::class => function() {
                    return new Functions\UserFunctions();
                },
                Controller\TestController::class => function ($container) {
                    return new Controller\TestController();
                },
                Controller\SugestedFriendListController::class => function() {
                    return new Controller\SugestedFriendListController();
                },
                Controller\UserCheckinController::class => function() {
                    return new Controller\UserCheckinController();
                },
                Controller\UserRestaurantImageController::class => function() {
                    return new Controller\UserRestaurantImageController();
                },
                Controller\RestaurantBookmarksController::class => function() {
                    return new Controller\RestaurantBookmarksController();
                },
                Controller\MenuBookmarksController::class => function() {
                    return new Controller\MenuBookmarksController();
                },
                Controller\UserActivityFeedController::class => function() {
                    return new Controller\UserActivityFeedController();
                },
                Controller\UserProfileUpdateController::class => function() {
                    return new Controller\UserProfileUpdateController();
                },
                Controller\UserAddressController::class => function() {
                    return new Controller\UserAddressController();
                },
                Controller\UserActionSettingController::class => function() {
                    return new Controller\UserActionSettingController();
                },
                Controller\ChangePasswordController::class => function() {
                    return new Controller\ChangePasswordController();
                },

                Controller\OrderPlaceController::class=>function(){
                    return new Controller\OrderPlaceController();
                },
                Controller\UserFeedbackController::class => function() {
                    return new Controller\UserFeedbackController();
                },
                Controller\UserReviewController::class => function() {
                    return new Controller\UserReviewController();
                    },

            ],
        ];
    }

}
