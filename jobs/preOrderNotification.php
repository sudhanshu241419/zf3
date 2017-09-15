<?php
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$reservationModel = new \User\Model\UserOrder();
$commonFunctiion = new \MCommons\CommonFunctions();
$userNotificationModel = new \User\Model\UserNotification();
$currentDate = StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT);

$joins [] = array(
    'name' => array(
        'uo' => 'restaurants'
    ),
    'on' => new Expression("(user_orders.restaurant_id = uo.id)"),
    'columns' => array(
        'city_id','restaurant_name'
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
$joins [] = array(
    'name' => array(
        'od' => 'user_order_details'
    ),
    'on' => new Expression("(od.user_order_id = user_orders.id)"),
    'columns' => array('order_items'=>new Expression("group_concat(concat(`quantity`,' ',`item`) separator ',')")),
    'type' => 'left'
);
$options = array(
    'columns' => array(
        'restaurant_id',
        'order_type',
        'delivery_time',
        'user_id',
        'id',
        'fname','lname','payment_receipt','created_at','phone','status','total_amount','status'
    ),
    'joins' => $joins,
    'where' => array('(user_orders.status = "cancelled" OR user_orders.status = "confirmed" OR user_orders.order_type = "Takeout" OR  user_orders.order_type = "Delivery") and cronUpdateNotification="0"'  ),
    'order'=>'id DESC',
    'group'=>'id',
);
$reservationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allTakeoutDelivery = $reservationModel->find($options)->toArray();
//pr($allTakeoutDelivery,1);
if(count($allTakeoutDelivery) > 0){
    foreach($allTakeoutDelivery as $key=>$values){
        $restaurantTime=StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $values['restaurant_id']
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
        $uname = (isset($values['lname']) && !empty($values['lname']))?$values ['fname']." ".$values['lname']:$values['fname'];
        // check wether the restaurant time is not less than delivery time.
        if(strtotime($values['delivery_time'])>strtotime($restaurantTime)){
            $currentTimeOrder = new \DateTime ();
            $arrivedTimeOrder = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $values['delivery_time']);
            $differenceOfTimeInMin = round(abs(strtotime($arrivedTimeOrder->format("Y-m-d H:i:s"))-strtotime($restaurantTime))/60);
            if($differenceOfTimeInMin<=90){
                if($values['user_id']>0){
                if(trim($values['order_type'])=== "Takeout"){
                 $notificationMsg ='Your takeout pre-order from ' . ucfirst($values['restaurant_name']) . ' will start preparations in an hour and a half!';
                }
                if(trim($values['order_type'])=== "Delivery"){
                $notificationMsg ='Your pre-order from ' . ucfirst($values['restaurant_name']) . ' will be on its way in just an hour and a half!';
                }
//                $channel = "mymunchado_" . $values['user_id'];
//                $notificationArray = array(
//                    "msg" => $notificationMsg,
//                    "channel" => $channel,
//                    "userId" => $values['user_id'],
//                    "type" => 'order',
//                    "restaurantId" => $values['restaurant_id'],
//                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
//                        'restaurant_id' => $values['restaurant_id']
//                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
//                    'restaurant_name'=>ucfirst($values['restaurant_name']),
//                    'order_id' => $values['id']
//                );
//                $notificationJsonArray = array('user_id'=>$values['user_id'],'order_id' => $values['id'],'restaurant_id' => $values['restaurant_id'],'restaurant_name'=>ucfirst($values['restaurant_name']));
//                $response = $userNotificationModel->createPubNubNotification($notificationArray,$notificationJsonArray);
//                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                $reservationModel->updateCronNotification($values['id']);
                }
            }
        }
        
        
    }
}