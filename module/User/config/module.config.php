<?php

namespace User;

use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'api-user-details' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/details[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserDetailsController::class,
                    ],
                ],
            ],
            'user-login' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/login[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\LoginController::class,
                    ]
                ]
            ],
            'user-logout' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/logout[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\LogoutController::class,
                    ]
                ]
            ],
            'api-user-orders' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/orders',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserOrderController::class,
                    ],
                ],
            ],
            'api-user-promo-codes' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/promocodes',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserPromoCodesController::class,
                    ],
                ],
            ],
            'api-user-referral-code' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/referralcode[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserReferralCodeController::class,
                    ],
                ],
            ],
            'api-user-current-notification' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/current-notification[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserCurrentNotificationController::class,
                    ],
                ],
            ],
            'api-user-location' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/location[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\LocationController::class,
                    ],
                ],
            ],
            'api-user-points' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/userpoints[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserPointsController::class,
                    ],
                ],
            ],
            'api-user-address' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/address',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserAddressController::class,
                    ],
                ],
            ],
            'api-user-mymunchers' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/mymuncher[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\MyMuncherController::class,
                    ],
                ],
            ],
            'api-user-friends' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/friends[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserFriendController::class,
                    ],
                ],
            ],
            'api-test' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/test[/:id]',
                    'defaults' => [
                        'controller' => Controller\TestController::class,
                    ],
                ],
            ],
            'api-find-friends' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/sugestedfriend[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\SugestedFriendListController::class,
                    ],
                ],
            ],
            'api-my-checkin' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/checkin[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserCheckinController::class,
                    ],
                ],
            ],
            'api-user-restaurant-photo' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/addphoto[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserRestaurantImageController::class,
                    ],
                ],
            ],
            'api-user-restaurant-bookmarks' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/restaurant-bookmarks',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\RestaurantBookmarksController::class,
                    ],
                ],
            ],
            'api-user-menu-bookmarks' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/menu-bookmarks',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\MenuBookmarksController::class,
                    ],
                ],
            ],
            'api-user-feed' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/feed[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserActivityFeedController::class,
                    ],
                ],
            ],
            'api-user-profile-update' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/user-profile-update[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserProfileUpdateController::class,
                    ],
                ],
            ],
            'api-user-address' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/address[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserAddressController::class,
                    ],
                ],
            ],
            'api-user-action-setting' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/actionsetting[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\UserActionSettingController::class,
                    ],
                ],
            ],
            'api-user-change-password' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/changepassword[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ChangePasswordController::class,
                    ],
                ],
            ],
          
            'user-oreder-place' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/orderplace[/:id]',
                    'constraints' => [
                        'id' => '[a-zA-Z0-9]+',
                    ],
                    'defaults' => [
                        'controller' => 'User\Controller\OrderPlace'
                    ],
                ],
            ],

            'api-user-feedback' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/userfeedback',
                    'defaults' => [
                        'controller' => Controller\UserFeedbackController::class,
                    ],
                ],
            ],
            'api-user-review' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/api/user/review[/:id]',
                    'defaults' => [
                        'controller' => Controller\UserReviewController::class,
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'email-layout/default' => __DIR__ . '/../Mails/User/layouts/default.phtml',
            'email-layout/default_new' => __DIR__ . '/../Mails/User/layouts/default_new.phtml',
            'email-layout/default_register' => __DIR__ . '/../Mails/User/layouts/default_register.phtml',
            'email-layout/default_app' => __DIR__ . '/../Mails/User/layouts/default_app.phtml',
            'email-layout/default_android_app' => __DIR__ . '/../Mails/User/layouts/default_android_app.phtml',
            'email-template/send_Invitation' => __DIR__ . '/../Mails/User/templates/send_Invitation.phtml',
            'email-template/forgot-password' => __DIR__ . '/../Mails/User/templates/forgot_password.phtml',
            'email-template/friends-Invitation' => __DIR__ . '/../Mails/User/templates/03_Joe-Gave-Us-Your-Email.-That\'s-Cool-Right.phtml',
            'email-template/friends-reservation-Invitation' => __DIR__ . '/../Mails/User/templates/09_Someone-Wants-To-Grab-Food-With-You.phtml',
            'email-template/send-cancel-reservation' => __DIR__ . '/../Mails/User/templates/23_Bad-News-About-a-Munchado-Reservation.phtml',
            'email-template/send-cancel-reservation-owner' => __DIR__ . '/../Mails/User/templates/23_Bad-News-About-a-MunchAdo-Reservation_Owner.phtml',
            'email-template/send-cancel-order-user' => __DIR__ . '/../Mails/User/templates/14_Your-Pre-Sceduled-Order-Has-Been-Pre-Canceled.phtml',
            'email-template/modify-reservation' => __DIR__ . '/../Mails/User/templates/06_Your-Modifications-Were-Successful.phtml',
            'email-template/place-order-takeout' => __DIR__ . '/../Mails/User/templates/order-takeout.phtml',
            'email-template/place-order-delivery' => __DIR__ . '/../Mails/User/templates/order-delivery.phtml',
            'email-template/user-registration' => __DIR__ . '/../Mails/User/templates/01_Welcome-Friend.phtml',
            'email-template/user-reservation-confirmation' => __DIR__ . '/../Mails/User/templates/reservation-placed.phtml',
            'email-template/munchado-customer-reservation-confirmation' => __DIR__ . '/../Mails/User/templates/22_Reservation-From-a-MunchAdo-Customer.phtml',
            'email-template/social-media-registration' => __DIR__ . '/../Mails/User/templates/02_Weclome-Social-Media-Savant.phtml',
            'email-template/modify-reservation-to-friends' => __DIR__ . '/../Mails/User/templates/31_Modification_Reservation_Invitee.phtml',
            'email-template/modify-reservation-to-owner' => __DIR__ . '/../Mails/User/templates/32_One_of_Your_Reservations_Has_Changed.phtml',
            'email-template/review-mail-to-owner' => __DIR__ . '/../Mails/User/templates/27_Someone-Posted-a-New-Review-for-You-on-HungryBuzz.phtml',
            'email-template/send-cancel-reservation-friends' => __DIR__ . '/../Mails/User/templates/EPIC-Fail.phtml',
            'email-template/preorder-user-mail' => __DIR__ . '/../Mails/User/templates/34_Planning-Ahead-We-see.phtml',
            'email-template/preorder-user-tackout-mail' => __DIR__ . '/../Mails/User/templates/35_Planning-Ahead-We-see-tackout.phtml',
            'email-template/food-there' => __DIR__ . '/../Mails/User/templates/Foods-There.phtml',
            'email-template/owners-response' => __DIR__ . '/../Mails/User/templates/Owner-response.phtml',
            'email-template/order-detail-service-provider' => __DIR__ . '../Mails/User/templates',
            'email-template/place-order-delivery-service-provider' => __DIR__ . '/../Mails/User/templates/order-delivery-service-provider.phtml',
            'email-template/preorder-user-mail-service-provider' => __DIR__ . '/../Mails/User/templates/34_Planning-Ahead-We-see-service-provider.phtml',
            'email-template/Pre-Ordered_Pre-Reservation_Pre-Approved' => __DIR__ . '/../Mails/User/templates/Pre-Ordered_Pre-Reservation_Pre-Approved.phtml',
            'email-template/New_Pre-Paid_Reservation' => __DIR__ . '/../Mails/User/templates/New_Pre-Paid_Reservation.phtml',
            'email-template/You_Us_Them_And_Food' => __DIR__ . '/../Mails/User/templates/You_Us_Them_And_Food.phtml',
            'email-template/Came_In_Like_A_Wrecking_Ball' => __DIR__ . '/../Mails/User/templates/Came_In_Like_A_Wrecking_Ball.phtml',
            'email-template/Turned_Down_For_What' => __DIR__ . '/../Mails/User/templates/Turned_Down_For_What.phtml',
            'email-template/Its_a_date_And_time_And_food' => __DIR__ . '/../Mails/User/templates/Its_a_date_And_time_And_food.phtml',
            'email-layout/default_email_subscription' => __DIR__ . '/../Mails/User/layouts/default_email_subscription.phtml',
            'email-template/Email-Subscription' => __DIR__ . '/../Mails/User/templates/01_Email-Subscription.phtml',
            'email-template/Email-Subscription-registration' => __DIR__ . '/../Mails/User/templates/02_Email-Subscription-registration.phtml',
            'email-template/Email-Subscription-more-from-munchado' => __DIR__ . '/../Mails/User/templates/03_Email-Subscription-more-from-munchado.phtml',
            'email-template/five-doller-on-registration' => __DIR__ . '/../Mails/User/templates/01_five-doller-on-registration.phtml',
            'email-layout/05_registration' => __DIR__ . '/../Mails/User/layouts/05_registration.phtml',
            'email-template/05_So_They_Get_Another_Coupon' => __DIR__ . '/../Mails/User/templates/05_So_They_Get_Another_Coupon.phtml',
            'email-layout/default_email_subscription_without_10' => __DIR__ . '/../Mails/User/layouts/default_email_subscription_without_10.phtml',
            'email-template/05_Your-Reservation-has-been-reserved' => __DIR__ . '/../Mails/User/templates/05_Your-Reservation-has-been-reserved.phtml',
            'email-template/is_buying_food_you_in' => __DIR__ . '/../Mails/User/templates/is_buying_food_you_in.phtml',
            'email-template/redemption_cashback' => __DIR__ . '/../Mails/User/templates/redemption_cashback.phtml',
            'email-template/redemption_opennight' => __DIR__ . '/../Mails/User/templates/redemption_opennight.phtml',
            'email-template/newcard' => __DIR__ . '/../Mails/User/templates/newcard.phtml',
            'email-template/edu_subscriber' => __DIR__ . '/../Mails/User/templates/edu_subscriber.phtml',
            'email-template/inviteemail' => __DIR__ . '/../Mails/User/templates/inviteemail.phtml',
            'email-template/To_Go_For_30_From_Munch_Ado' => __DIR__ . '/../Mails/User/templates/To_Go_For_30_From_Munch_Ado.phtml',
            'email-template/Turn_Your_5_Cash_Back_into_Another_30' => __DIR__ . '/../Mails/User/templates/Turn_Your_5_Cash_Back_into_Another_30.phtml',
            'email-template/emailawarding' => __DIR__ . '/../Mails/User/templates/emailawarding.phtml',
            'email-layout/default_emailer' => __DIR__ . '/../Mails/User/layouts/default_emailer.phtml',
            'email-template/app_released' => __DIR__ . '/../Mails/User/templates/app_released.phtml',
            'email-template/app_notification_subscriber' => __DIR__ . '/../Mails/User/templates/app_notification_subscriber.phtml',
            'email-template/android_app_notify' => __DIR__ . '/../Mails/User/templates/android_app_notify.phtml',
            'email-template/Turned_Down_For_What_normal_reservation' => __DIR__ . '/../Mails/User/templates/Turned_Down_For_What_normal_reservation.phtml',
            'email-template/agreed_to_join_munchado' => __DIR__ . '/../Mails/User/templates/agreed_to_join_munchado.phtml',
            'email-template/promo-alert' => __DIR__ . '/../Mails/User/templates/02_to_users_coupon-expires.phtml',
            'email-template/you_did_it_again' => __DIR__ . '/../Mails/User/templates/you_did_it_again.phtml',
            'email-layout/you_did_it_again' => __DIR__ . '/../Mails/User/layouts/you_did_it_again.phtml',
            'email-template/loyaltyregister' => __DIR__ . '/../Mails/User/templates/loyaltyRegister.phtml',
            'email-layout/default_dineandmore' => __DIR__ . '/../Mails/User/layouts/default_dineandmore.phtml',
            'email-template/Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password' => __DIR__ . '/../Mails/User/templates/Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password.phtml',
            'email-template/Welcome_To_Restaurant_Dine_More_Rewards_Exist_User' => __DIR__ . '/../Mails/User/templates/Welcome_To_Restaurant_Dine_More_Rewards_Exist_User.phtml',
            'email-template/Welcome_To_Restaurant_Dine_More_Rewards_From_Site_App' => __DIR__ . '/../Mails/User/templates/Welcome_To_Restaurant_Dine_More_Rewards_From_Site_App.phtml',
            'email-layout/referral' => __DIR__ . '/../Mails/User/layouts/referral.phtml',
            'email-template/join_at_munchado' => __DIR__ . '/../Mails/User/templates/join_at_munchado.phtml',
            'email-template/server-registration' => __DIR__ . '/../Mails/User/templates/01_Welcome-server-registration.phtml',
            'email-layout/default_server_register' => __DIR__ . '/../Mails/User/layouts/default_server_register.phtml',
            'email-layout/default_update_reservation' => __DIR__ . '/../Mails/User/layouts/default_update_reservation.phtml',
            'email-template/munchado_career' => __DIR__ . '/../Mails/User/templates/career.phtml',
            'email-layout/default_career' => __DIR__ . '/../Mails/User/layouts/default_career.phtml',
            'email-layout/default_career_bravvura' => __DIR__ . '/../Mails/User/layouts/default_career_bravvura.phtml',
            'email-template/user-snag-a-spot-placed' => __DIR__ . '/../Mails/User/templates/user-snag-a-spot-placed.phtml',
            'email-template/sms_offer_mail' => __DIR__ . '/../Mails/User/templates/sms_offer_mail.phtml',
            'email-template/registration_from_micro_site' => __DIR__ . '/../Mails/MA/templates/registration_from_micro_site.phtml',
            'email-layout/ma_default' => __DIR__ . '/../Mails/MA/layouts/default.phtml',
            'email-template/registration_from_micro_site_with_dine_more_code' => __DIR__ . '/../Mails/MA/templates/registration_from_micro_site_with_dine_more_code.phtml',
            'email-template/registration_from_micro_site_with_dine_more_code_exist_user' => __DIR__ . '/../Mails/MA/templates/registration_from_micro_site_with_dine_more_code_exist_user.phtml',
            'email-template/ma_micro_order_confirm' => __DIR__ . '/../Mails/MA/templates/ma_micro_order_confirm.phtml',
            'email-template/microsite_carrier' => __DIR__ . '/../Mails/MA/templates/microsite_carrier.phtml',
            'email-template/microsite_alberto_enquiry' => __DIR__ . '/../Mails/MA/templates/microsite_alberto_enquiry.phtml',
            'email-layout/ma_alberto_default' => __DIR__ . '/../Mails/MA/layouts/alberto_default.phtml',
            'email-template/ma_forgot_password' => __DIR__ . '/../Mails/MA/templates/ma_forgot_password.phtml',
            'email-template/ma_friends-reservation-Invitation' => __DIR__ . '/../Mails/MA/templates/ma_Someone-Wants-To-Grab-Food-With-You.phtml'
        ],
        'template_path_stack' => [
            __DIR__ . '/../Mails'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ],
    ],
];
