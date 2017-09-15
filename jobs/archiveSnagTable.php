<?php

use MCommons\StaticOptions;
use Restaurantdinein\Model\Restaurantdinein;

include_once realpath(__DIR__ . "/../") . "/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$object = new Restaurantdinein();
$options = array(
            'archive' => 0,
            'orderBy' => 'hold_time ASC'
        );
$dineDetails = $object->dashboardRestaurantDineinList($options);
if (count($dineDetails) > 0) {
    foreach ($dineDetails as $key => $val) {
        $currDateTime = StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $val['restaurant_id']
        ));
        $datetime1 = $currDateTime->format(StaticOptions::MYSQL_DATE_FORMAT);
        $dineinFunctions = new \Restaurantdinein\RestaurantdineinFunctions();
        $timeToReservation = $dineinFunctions->holdTableDateTime($val['hold_time'], $val['reservation_date']);
        $datetimeObject = new DateTime($timeToReservation);
        $datetime2 = $datetimeObject->format(StaticOptions::MYSQL_DATE_FORMAT);
        $start = strtotime($datetime2);
        $end = strtotime($datetime1);
        $diff = $end - $start;
        $min = intval(($diff / 60) % 60);
        if ($min > 4) {
            $data = array(
            'archive' => 1
        );
            $object->update($val['reservation_id'], $data);
        }
    }
}