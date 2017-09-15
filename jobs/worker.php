<?php

/*
  ############### How to run #################
  QUEUE=default VERBOSE=1 APPLICATION_ENV=local php worker.php > log_worker.txt &
  ##################--########################
 */

use MCommons\StaticOptions;

include_once realpath(__DIR__ . "/../") . "/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
require_once dirname(__FILE__) . "/UploadS3.php";
Resque::setBackend($config['constants']['redis']['host'] . ":" . $config['constants']['redis']['port']);
$item = Resque::getList($config['constants']['redis']['channel']);
$salesManago = new Salesmanago();

if (count($item) > 0 && !empty($item)) {
    foreach ($item as $key => $val) {
        $jobs = json_decode($val, true);
        if ($jobs['class'] === 'SendEmail') {
            StaticOptions::sendMail($jobs['args'][0]['sender'], $jobs['args'][0]['sendername'], $jobs['args'][0]['receivers'][0], $jobs['args'][0]['template'], $jobs['args'][0]['layout'], $jobs['args'][0]['variables'], $jobs['args'][0]['subject']);
            Resque::pop($config['constants']['redis']['channel']);
        }
    }
}

//    elseif($jobs['class'] ==='Salesmanago'){
//        $identifier=$jobs['args'][0]['identifier'];
//        switch($identifier){
//            case 'register':
//            $salesManago->registerOnSalesmanago($jobs['args'][0],SERVER_FOR_SALESMANAGO);
//            break;
//            case 'phone':
//            $salesManago->updatePhoneOnSalesmanago($jobs['args'][0]);
//            break;
//            case 'earned':
//            $salesManago->earnPointOnSalesmanago($jobs['args'][0]);
//            break;
//            case 'redeemed':
//            $salesManago->redeemedPointOnSalesmanago($jobs['args'][0]);
//            break;
//            case 'event':
//            $salesManago->eventsOnSalesmanago($jobs['args'][0]);
//            break;
//            case 'custome':
//            $salesManago->customeDetailSalesmanago($jobs['args'][0]);    
//            break;
//        }
//        Resque::pop($config['constants']['redis']['channel']);
//   }
