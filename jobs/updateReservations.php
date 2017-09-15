<?php
use MCommons\StaticOptions;
use User\Model\UserReservation;
use Zend\Db\Sql\Predicate\Expression;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$reservationModel = new UserReservation();
$joins [] = array(
    'name' => array(
        'uo' => 'restaurants'
    ),
    'on' => new Expression("(user_reservations.restaurant_id = uo.id)"),
    'columns' => array(
        'city_id'
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
$options = array(
    'joins' => $joins,
    'where' => array('user_reservations.status' => array(1, 3, 4), 'cron_status'=>'0')
);
$reservationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allReservation = $reservationModel->find($options)->toArray();
if (!empty($allReservation)) {
    foreach ($allReservation as $key => $value) {
        if ($value ['time_zone'] == null) {
            $reservationModel->setReservationStatusArchived($value);
        } else {
            $currentTime = new \DateTime ();
            $currentTime->setTimezone(new \DateTimeZone($value ['time_zone']));
            $arrivedTime = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $value ['time_slot'], new \DateTimeZone($value ['time_zone']));
            $arrivedTimeModified= $arrivedTime->modify('+45 minutes');
           
            if (strtotime($currentTime->format("Y-m-d H:i:s")) > strtotime($arrivedTimeModified->format("Y-m-d H:i:s"))) {
               $reservationModel->setReservationStatusArchived($value);
            }
        }
    }
}