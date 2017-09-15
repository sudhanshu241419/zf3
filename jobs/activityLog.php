<?php

/*
############### How to run #################
QUEUE=default VERBOSE=1 APPLICATION_ENV=local php worker.php > log_worker.txt &
##################--########################
*/
use MCommons\StaticOptions;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
//require_once dirname(__FILE__) . "/UploadS3.php";
Resque::setBackend($config['constants']['activityLogRedis']['host'] . ":" . $config['constants']['activityLogRedis']['port']);
$item = Resque::getList($config['constants']['activityLogRedis']['channel']);

foreach($item as $key => $val){
    $jobs = json_decode($val, true);  
    
    if(isset($jobs['args'][0]['privacy_status'])){
        $feedData = json_decode($jobs['args'][0]['feed'],true);
        print_r($feedData);
    }else{
        print_r($jobs['args'][0]);
    }
}

