<?php

use MCommons\StaticOptions;
use User\Model\UserReferrals;
use User\Model\RestaurantServer;
use User\Model\UserOrder;
use User\UserFunctions;
use User\Model\User;
use MCommons\CommonFunctions;
use User\Model\UserNotification;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$restaurantServer = new RestaurantServer();
$userReferral = new UserReferrals();
$userOrder = new UserOrder();
$userFunctions = new UserFunctions();
$user = new User();
$commonFunctiion = new CommonFunctions();
$userName = false;
$userServerList = $restaurantServer->getInactiveUserFromRestaurantServer();
$userNotificationModel = new UserNotification();
if (count($userServerList) > 0) {
    foreach ($userServerList as $key => $val) {
        if(ucfirst($val['code'])!="M100000"){
            $userConfirmOrder = $userOrder->getConfirmOrder($val['user_id'], $val['restaurant_id']);
            $userFunctions->userId = $val['user_id'];
            $userName = $user->getName($val['user_id']);
            $userFunctions->restaurantId = $val['restaurant_id'];
            $userFunctions->parseLoyaltyCode($val['code']);
            if (!empty($userConfirmOrder)) {
                $referralDetail = $userReferral->getReferralDetails($val['user_id'], $val['restaurant_id']);
                if (!empty($referralDetail)) {                  
                    $inviterPoints = $userFunctions->getAllocatedPoints("dinemorereferralinvitee");
                    $inviterPointMessage = "Your friend placed their first order!";
                    $userFunctions->giveReferralPoints($inviterPoints, $referralDetail[0]['inviter_id'], $inviterPointMessage);
                    $notificationMsg= "Your friend placed their first order!";
                    $channel = "mymunchado_" . $referralDetail[0]['inviter_id'];
                   
                    $notificationArray = array(
                        "msg" => $notificationMsg,
                        "channel" => $channel,
                        "userId" => $val['user_id'],
                        "friend_iden_Id" => $referralDetail[0]['inviter_id'],
                        "type" => 'referral_first_order',
                        "restaurantId" => $val['restaurant_id'],
                        'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                            'restaurant_id' => $val['restaurant_id']
                                     ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                        'username' => ''
                    );
                    $notificationJsonArrayToUser = array('user_id' =>  $val['user_id'],'inviter_id'=>$referralDetail[0]['inviter_id'], $this->typeKey => 'referral_first_order', 'restaurant_id' => $val['restaurant_id'], 'restaurant_name' => "");
                    $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArrayToUser);
                    StaticOptions::pubnubPushNotification($notificationArrayToUser);
                }
                              
                $data['status'] = 1;
                $restaurantServer->id = $val['id'];
                $restaurantServer->update($data);                
            }
        }
    }
}
