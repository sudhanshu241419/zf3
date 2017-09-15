<?php

/* 
 * This file is use to send notification to checkin user friend
 */

use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$userNotificationModel = new \User\Model\UserNotification();
$userFriendModel = new \User\Model\UserFriends();
$joins [] = array(
    'name' => array(
        'uo' => 'restaurants'
    ),
    'on' => new Expression("(pubnub_notification.restaurant_id = uo.id)"),
    'columns' => array(
       'restaurant_name'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'ci' => 'cities'
    ),
    'on' => new Expression("(ci.id = uo.city_id)"),
    'columns' => array(
        'time_zone'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'u' => 'users'
    ),
    'on' => new Expression("(u.id = pubnub_notification.user_id)"),
    'columns' => array(
        'first_name','last_name'
    ),
    'type' => 'inner'
);
$options = array(
    'columns' => array(
        'user_id',
        'restaurant_id',
        'id',
        'pubnub_info'
    ),
    'joins' => $joins,
    'where' => array('pubnub_notification.cronUpdate = "0" and pubnub_notification.type ="11"' )
);
$userNotificationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allNotification = $userNotificationModel->find($options)->toArray();

if(count($allNotification)>0){
    $notification=array('checkIn'=>1,'checkFriend'=>2,'checkMenu'=>3,'checkPhoto'=>4,'checkMenuFriend'=>5,'checkMenuPhoto'=>6,'checkMenuPhotoFriend'=>7);
    foreach($allNotification as $key=>$notVal){
        if($notVal['user_id']>0){
        $pubInfo=  json_decode($notVal['pubnub_info']);
        if(count($pubInfo)>0){
            $myMenu = ''; 
            $menuInfo=array();
            $menuInfo1=array();
            $menuIds=array();
            $myMenuIds = '';
            if(isset($pubInfo->menuinfo) && !empty($pubInfo->menuinfo)){  
                foreach($pubInfo->menuinfo as $fkey => $fvalue){
                    if(isset($fvalue->name)){ 
                        $myMenu .= $fvalue->name.", ";
                        $menuInfo1['name']=$fvalue->name;
                    }
                    if(isset($fvalue->id)){ 
                        $myMenuIds .= $fvalue->id.", ";
                        $menuInfo1['id']=$fvalue->id;
                    }
                    $menuInfo[]=$menuInfo1;
                  
                }
                if(!empty($myMenu)){
                 $myMenu = substr($myMenu,0,-2);
                }
            }
            
            $myFriend = ''; 
            $friendInfo=array();
            $friendInfo1=array();
            $myFriendIds = ''; 
            if(isset($pubInfo->friendinfo) && !empty($pubInfo->friendinfo)){  
                foreach($pubInfo->friendinfo as $fkey => $fvalue){
                    if(isset($fvalue->name)){
                        $myFriend .= $fvalue->name.", "; 
                        $friendInfo1['name']=$fvalue->name;
                    }
                    if(isset($fvalue->id)){
                        $myFriendIds.= $fvalue->id.", "; 
                        $friendInfo1['id']=$fvalue->id;
                    } 
                    $friendInfo[]=$friendInfo1;
                }
                if(!empty($myFriend)){
                 $myFriend = substr($myFriend,0,-2);
                }
            }
            $notificationType=$notification[$pubInfo->type];
            $userFriendList=$userFriendModel->getUserFriendList($pubInfo->user_id, $orderby="");
            if($notificationType==1){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =  ucfirst($pubInfo->username).' just walked in to '.ucfirst($pubInfo->restaurant_name).'. Their mind must be blown! Or their stomachs empty. We always confuse those feelings.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1
                            );
                            $notificationJsonArray = array('user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
            
            if($notificationType==2){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg = $pubInfo->username.' is with '.$myFriend.' at '.ucfirst($pubInfo->restaurant_name).'. Adorable.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'friendinfo' => $friendInfo,
                                'cronUpdate'=>1
                            );
                            $notificationJsonArray = array('friendinfo' => $friendInfo, 'user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name), 'type' => 'checkFriend');
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                if($notificationType==3){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =$pubInfo->username.' is enjoying a taste of the good life with the '.$myMenu.' at '.ucfirst($pubInfo->restaurant_name).'.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'menuinfo'=>$menuInfo,
                                'cronUpdate'=>1
                            );
                            $notificationJsonArray = array('menuinfo'=>$menuInfo, 'user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name), 'type' => 'checkMenu');
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                if($notificationType==4){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =$pubInfo->username.' posted a pic of '.ucfirst($pubInfo->restaurant_name).'. It’s got a certain je ne sais quoi.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1
                            );
                            $notificationJsonArray = array('user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name), 'type' => 'checkPhoto');
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                 if($notificationType==5){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =$pubInfo->username.' checked in at '.ucfirst($pubInfo->restaurant_name).' and ordered the '.$myMenu.' with '.$myFriend.'.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'menuinfo'=>$menuInfo,
                                'friendinfo' => $friendInfo
                            );
                            $notificationJsonArray = array('friendinfo' => $friendInfo,'menuinfo'=>$menuInfo,'user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name), 'type' => 'checkMenuFriend');
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                if($notificationType==6){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =$pubInfo->username.' checked in at '.ucfirst($pubInfo->restaurant_name).' and ordered the '.$myMenu.' with a side of a photo shoot.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'menuinfo'=>$menuInfo                                
                            );
                            $notificationJsonArray = array('menuinfo'=>$menuInfo,'user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name), 'type' => 'checkMenuPhoto');
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                if($notificationType==7){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =$pubInfo->username.' shared a pic while checking in at '.ucfirst($pubInfo->restaurant_name).' and ordering '.$myMenu.' with '.$myFriend.'.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $userF['friend_id'],
                                'username' => ucfirst($friendName),
                                "type" => 'checkin',
                                "restaurantId" => $pubInfo->restaurant_id,
                                "restaurant_name" => ucfirst($pubInfo->restaurant_name),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $pubInfo->restaurant_id
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'menuinfo'=>$menuInfo,
                                'friendinfo' => $friendInfo
                            );
                            $notificationJsonArray = array('friendinfo' => $friendInfo,'menuinfo'=>$menuInfo,'user_id' => $userF['friend_id'], 'username' => ucfirst($friendName), 'restaurant_id' => $pubInfo->restaurant_id, 'restaurant_name' => ucfirst($pubInfo->restaurant_name), 'type' => 'checkMenuPhotoFriend');
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
            $userNotificationModel->updateCronNotification($notVal['id']);
        }   
        
        
        }
    }
}

