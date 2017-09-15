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
        'pubnub_info','cronUpdate','type'
    ),
    'joins' => $joins,
    'where' => array('pubnub_notification.cronUpdate = "0" and pubnub_notification.type !="11"' )
);
$userNotificationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allNotification = $userNotificationModel->find($options)->toArray();

if(count($allNotification)>0){
    foreach($allNotification as $key=>$notVal){
        if($notVal['user_id']>0){
        $pubInfo=  json_decode($notVal['pubnub_info']);
        if(count($pubInfo)>0){
            $hostName=($notVal['last_name']!='')?$notVal['first_name'].' '.$notVal['last_name']:$notVal['first_name'];
            $resName=trim($notVal['restaurant_name']);
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
            
            $userFriendList=$userFriendModel->getUserFriendList($pubInfo->user_id, $orderby="");
            //send notification for order to host friend
            if($notVal['type']==1){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg=ucfirst($hostName).' ordered from '.ucfirst($resName).' without you. Stalk their life';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'order',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'order_id' => vefifyField($pubInfo->order_id)
                            );
                            $notificationJsonArray = array('order_id' => vefifyField($pubInfo->order_id), 'user_id' => $notVal['user_id'], 'username' => ucfirst($hostName), 'restaurant_id' => $notVal['restaurant_id'], 'restaurant_name' => ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                //send notification for reservation to host friend
                if($notVal['type']==3){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg=ucfirst($hostName).' reserved a table at '.ucfirst($resName).'. We’ve heard good things.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $friendName=($userF['last_name']!='')?$userF['first_name'].' '.$userF['last_name']:$userF['first_name'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'reservation',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'is_friend'=>1,
                                'cronUpdate'=>1,
                                'reservation_id'=>vefifyField($pubInfo->reservation_id),
                                'reservation_status'=>vefifyField($pubInfo->reservation_status),
                                'first_name'=>ucfirst($notVal['first_name'])
                            );
                            $notificationJsonArray = array('first_name'=>ucfirst($notVal['first_name']),'is_friend'=>1,'username'=>ucfirst($hostName),'reservation_id'=>vefifyField($pubInfo->reservation_id),'user_id'=>$notVal['user_id'],'reservation_status'=>vefifyField($pubInfo->reservation_status),'restaurant_id' => $notVal['restaurant_id'],'restaurant_name'=>ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                //send notification for review to host friend
                if($notVal['type']==4){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg=ucfirst($hostName).' shared some thoughts on '.ucfirst($resName).' Read them now or pretend you read them later.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'reviews',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'review_id'=>vefifyField($pubInfo->review_id),
                                'first_name'=>ucfirst($notVal['first_name']),
                                'restaurant_exist'=>1,
                                'is_friend'=>1
                            );
                            $notificationJsonArray = array('is_friend'=>1,'user_id'=>$notVal['user_id'],'review_id'=>vefifyField($pubInfo->review_id),'first_name'=>ucfirst($notVal['first_name']),'restaurant_exist'=>1,'restaurant_id' => $notVal['restaurant_id'],'restaurant_name'=>ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                 //send notification for tip to host friend
                 if($notVal['type']==7){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =ucfirst($hostName).' is tipping people off about '.ucfirst($resName).'.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'tip',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'isfriend'=>1,
                                'first_name'=>ucfirst($notVal['first_name'])
                            );
                             $notificationJsonArray = array('first_name'=>ucfirst($notVal['first_name']),'isfriend'=>1,'username'=>ucfirst($hostName),"user_id" => $notVal['user_id'],"restaurant_id" => $notVal['restaurant_id'],'restaurant_name'=>$notVal['restaurant_name']);
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                if($notVal['type']==8){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            $notificationMsg =ucfirst($hostName).' uploaded some pics. They’re not half bad.';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'upload_photo',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'upload_photo_status'=>vefifyField($pubInfo->upload_photo_status)                                
                            );
                            $notificationJsonArray = array('username' => ucfirst($hostName),"user_id" => $notVal['user_id'],'upload_photo_status'=>$pubInfo->upload_photo_status,"restaurant_id" => $notVal['restaurant_id'],'restaurant_name'=>  ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                        }
                    }
                    //sent notification to friend
                }
                if($notVal['type']==9){
              if (count($userFriendList) > 0) {
                        foreach ($userFriendList as $key => $userF) {
                            //love food bookmark notification sent to host friend
                            if(vefifyField($pubInfo->is_food)==1 && vefifyField($pubInfo->btype)==0){
                            $notificationMsg =ucfirst($hostName).' just lovessssssss '.html_entity_decode(vefifyField($pubInfo->menu_name)).'. Can you blame‘em?';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'bookmark',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'menu_id'=>vefifyField($pubInfo->menu_id),
                                'menu_name'=>html_entity_decode(vefifyField($pubInfo->menu_name)),
                                'first_name'=>ucfirst($notVal['first_name']),
                                'is_food'=>1,'btype'=>0
                            );
                            $notificationJsonArray = array('menu_id'=>vefifyField($pubInfo->menu_id),
                                'menu_name'=>html_entity_decode(vefifyField($pubInfo->menu_name)),
                                'btype'=>0,
                                'first_name'=>ucfirst($notVal['first_name']),
                                'user_id'=>$notVal['user_id'],
                                'is_food'=>1,
                                'restaurant_id' =>$notVal['restaurant_id'],
                                'restaurant_name'=>ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                            }
                            if(vefifyField($pubInfo->is_food)==1 && vefifyField($pubInfo->btype)==1){
                            $notificationMsg =ucfirst($hostName).' is craving '.html_entity_decode(vefifyField($pubInfo->menu_name)).'. Splitsies?';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'bookmark',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'menu_id'=>vefifyField($pubInfo->menu_id),
                                'menu_name'=>html_entity_decode(vefifyField($pubInfo->menu_name)),
                                'first_name'=>ucfirst($notVal['first_name']),
                                'is_food'=>1,'btype'=>1
                            );
                            $notificationJsonArray = array('menu_id'=>vefifyField($pubInfo->menu_id),
                                'menu_name'=>html_entity_decode(vefifyField($pubInfo->menu_name)),
                                'btype'=>1,
                                'first_name'=>ucfirst($notVal['first_name']),
                                'user_id'=>$notVal['user_id'],
                                'is_food'=>1,
                                'restaurant_id' =>$notVal['restaurant_id'],
                                'restaurant_name'=>ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                            }
                            if(vefifyField($pubInfo->is_food)==0 && vefifyField($pubInfo->btype)==0){
                            $notificationMsg =ucfirst($hostName).' liked '.ucfirst($resName).'. Do you care?';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'bookmark',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'first_name'=>ucfirst($notVal['first_name']),
                                'is_food'=>0,'btype'=>0
                            );
                            $notificationJsonArray = array('btype'=>0,
                                'first_name'=>ucfirst($notVal['first_name']),
                                'user_id'=>$notVal['user_id'],
                                'is_food'=>0,
                                'restaurant_id' =>$notVal['restaurant_id'],
                                'restaurant_name'=>ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                            }
                            if(vefifyField($pubInfo->is_food)==0 && vefifyField($pubInfo->btype)==1){
                            $notificationMsg =ucfirst($hostName).' is craving '.ucfirst($resName).'. Did someone say group outing?';
                            $channel = "mymunchado_" . $userF['friend_id'];
                            $notificationArray = array(
                                "msg" => $notificationMsg,
                                "channel" => $channel,
                                "userId" => $notVal['user_id'],
                                "friend_iden_Id"=>$userF['friend_id'],
                                'username' => ucfirst($hostName),
                                "type" => 'bookmark',
                                "restaurantId" => $notVal['restaurant_id'],
                                "restaurant_name" => ucfirst($resName),
                                'curDate' => StaticOptions::getRelativeCityDateTime(array(
                                    'restaurant_id' => $notVal['restaurant_id']
                                ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                                'cronUpdate'=>1,
                                'first_name'=>ucfirst($notVal['first_name']),
                                'is_food'=>0,'btype'=>1
                            );
                            $notificationJsonArray = array('btype'=>1,
                                'first_name'=>ucfirst($notVal['first_name']),
                                'user_id'=>$notVal['user_id'],
                                'is_food'=>0,
                                'restaurant_id' =>$notVal['restaurant_id'],
                                'restaurant_name'=>ucfirst($resName));
                            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                            $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                            }
                            
                        }
                    }
                    //sent notification to friend
                }
            $userNotificationModel->updateCronNotification($notVal['id']);
        }   
        
        
        }
    }
}

function vefifyField($field=false){
  if(isset($field) && $field!=''){
    return $field;  
  }else{
      return '';
  }  
}
