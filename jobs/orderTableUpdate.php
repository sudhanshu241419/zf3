<?php
require_once dirname ( __FILE__ ) . "/init.php";
use MCommons\StaticOptions;
use User\Model\UserCron;
use User\Model\UserOrder;

defined ( 'APPLICATION_ENV' ) || define ( 'APPLICATION_ENV', (getenv ( 'APPLICATION_ENV' ) ? getenv ( 'APPLICATION_ENV' ) : 'local') );

StaticOptions::setServiceLocator ( $GLOBALS ['application']->getServiceManager () );
$sl = StaticOptions::getServiceLocator ();
$config = $sl->get ( 'Config' );

$cronOrderModel = new UserCron();
$userOrderModel = new UserOrder();

$orderStatus = isset($config['constants']['order_status']) ? $config['constants']['order_status'] : array();
$status[] = $orderStatus[0];
$status[] = $orderStatus[1];
$status[] = $orderStatus[2];
$status[] = $orderStatus[3];
$status[] = $orderStatus[7];
$status[] = $orderStatus[8];

$response = $userOrderModel->updateOrderTable($status);
print_r($response);