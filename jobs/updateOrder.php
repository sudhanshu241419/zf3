<?php

use MCommons\StaticOptions;
use User\Model\UserCron;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$cronOrderModel = new UserCron ();
$options = array(
    'columns' => array(
        'id',
        'order_id',
        'arrived_time',
        'archive_time',
        'status',
        'time_zone'
    ),
    'where' => new \Zend\Db\Sql\Predicate\Expression('status != 2')
);
$cronOrderModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$response = $cronOrderModel->find($options)->toArray();
if (!empty($response)) {
    foreach ($response as $response) {
        if ($response ['time_zone'] == null) {
            $cronOrderModel->setOrderStatusArchived($response);
        } elseif ($response ['status'] == 0) {
            $currentTime = new \DateTime ();
            $currentTime->setTimezone(new \DateTimeZone($response ['time_zone']));
            $arrivedTime = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $response ['arrived_time'], new \DateTimeZone($response ['time_zone']));
            if ($currentTime > $arrivedTime) {
                $cronOrderModel->setOrderStatusArrived($response);
            }
        } elseif ($response ['status'] == 1) {
            $currentTime = new \DateTime ();
            $currentTime->setTimezone(new \DateTimeZone($response ['time_zone']));
            $archivedTime = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $response ['archive_time'], new \DateTimeZone($response ['time_zone']));
            if ($currentTime > $archivedTime) {
                $cronOrderModel->setOrderStatusArchived($response);
            }
        }
    }
}

