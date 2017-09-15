<?php

use MCommons\StaticOptions;
use Restaurantdinein\Model\Restaurantdinein;

include_once realpath(__DIR__ . "/../") . "/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$object = new Restaurantdinein();
$dateObject = new DateTime();
$interval = new DateInterval('P1D');
$dateObject->sub($interval);
$startDate = $dateObject->format("Y-m-d") . " 00:00:00";
$endDate = $dateObject->format("Y-m-d") . " 23:59:59";
$options = array(
    'status' => array(0, 1,2, 3, 4, 5,6),
    'start_date' => $startDate,
    'end_date' => $endDate,
    'orderBy' => 'hold_time ASC'
);
$dineDetails = $object->dashboardRestaurantDineinList($options);
if (count($dineDetails) > 0) {
    foreach ($dineDetails as $key => $val) {
        $data = array(
            'status' => 7
        );
        $object->update($val['reservation_id'], $data);
    }
}