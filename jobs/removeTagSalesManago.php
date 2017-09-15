<?php

/*
  ############### How to run #################
  QUEUE=default VERBOSE=1 APPLICATION_ENV=local php worker.php > log_worker.txt &
  ##################--########################
 */

use MCommons\StaticOptions;

//if (!getenv('APPLICATION_ENV') || !getenv('QUEUE')) {
//    echo "\nAPPLICATION_ENV OR QUEUE not defined.\n";
//    die('QUEUE=default VERBOSE=1 APPLICATION_ENV=local php ' . __FILE__ . "> workerlog.txt &\n");
//}

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));


require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();

$restaurantServer = new \User\Model\RestaurantServer();
$salesManago = new Salesmanago();
$unTagsDetails = $restaurantServer->getUserDineAndMoreUntagRestaurant();

if (count($unTagsDetails) > 0 && !empty($unTagsDetails)) {

    foreach ($unTagsDetails as $key => $val) {

        $userTagedDetails = $restaurantServer->userDineAndMoreRestaurant($val['user_id']);
        if (empty($userTagedDetails)) {
            $salesManago->removeTagsSalesmanago($val);
        }
    }
}



