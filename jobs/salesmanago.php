<?php

/*
############### How to run #################
QUEUE=default VERBOSE=1 APPLICATION_ENV=local php: salesmanago.php, event : salesmanago registration, userFunctions : eventsOnSalesmanago
##################--########################
*/
use MCommons\StaticOptions;
use User\UserFunctions;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$userFunctions = new UserFunctions();
Resque::setBackend($config['constants']['redis']['host'] . ":" . $config['constants']['redis']['port']);
$item = Resque::getList($config['constants']['redis']['channel']);
foreach($item as $key => $val){
    $jobs = json_decode($val, true);    
    if($jobs['class'] ==='Salesmanago'){       
        $response = $userFunctions->eventsOnSalesmanago($jobs['args'][0]);
        Resque::pop($config['constants']['redis']['channel']);
   }    
}

