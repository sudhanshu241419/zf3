<?php

use MCommons\StaticOptions;

defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$geocode_base_url = "http://maps.google.com/maps/api/geocode/json?&sensor=false";
$config = $sl->get('Config');
$restaurantdistance = new Restaurant\RestaurantDistanceCalculationFunction();
$OrderModel = new User\Model\UserOrder();

$options = array(
    'columns' => array(
        'id',
        'address',
        'state_code',
        'city',
        'zipcode'
    ),
    'where' => new \Zend\Db\Sql\Predicate\Expression('order_type = "delivery" and latitude =0 and longitude =0 AND zipcode <> "" ')
     
);
$OrderModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$response = $OrderModel->find($options)->toArray();
$OrderModel->latitude =0;
$OrderModel->longitude =0;
$OrderModel->id =0;

if (!empty($response)) {
    foreach ($response as $orderresponse) {
        $address = $orderresponse['address'].','.$orderresponse['city'].','.$orderresponse['state_code'].','.$orderresponse['zipcode'];
         $url = $geocode_base_url . "&address=" . urlencode($address);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_a = json_decode($response);
        $lat = 0;
        $long = 0;
        if (isset($response_a->results[0]->geometry->location)) {
            $lat = $response_a->results[0]->geometry->location->lat;
            $long = $response_a->results[0]->geometry->location->lng;
        }
        $OrderModel->id = $orderresponse['id'];
        $OrderModel->latitude = $lat; 
        $OrderModel->longitude = $long;
        $OrderModel->addtoUserOrder();
    }
    
}