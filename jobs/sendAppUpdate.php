<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
use MCommons\StaticOptions;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$userNotificationModel = new \User\Model\UserNotification();

$notificationMsg = "Updated app avialable";
$channel = "mymunchado_58285";
$notificationArray = array(
    "msg" => $notificationMsg,
    "channel" => $channel,            
    "type" => 'appupdate',
    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                'restaurant_id' => '58285'
            ))->format(StaticOptions::MYSQL_DATE_FORMAT),
            
);

$pubnub = StaticOptions::appUpdateNotification($notificationArray);
pr($pubnub,1);

