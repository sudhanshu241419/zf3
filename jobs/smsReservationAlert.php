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
$userNotificationModel = new \User\Model\UserNotification();
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
    'columns' => array(
        'restaurant_name',
        'reserved_seats',
        'phone',
        'time_slot',
        'user_id',
        'restaurant_id','id', 'order_id', 'host_name'
    ),
    'joins' => $joins,
    'where' => array('user_reservations.status = "4" and user_reservations.cron_status ="0" and cronUpdate="0"' )
);
$reservationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allReservation = $reservationModel->find($options)->toArray();
$cronUpdate=0;
if (!empty($allReservation)) {
    foreach ($allReservation as $key => $value) {
            $currentTime = new \DateTime ();
            $currentTime->setTimezone(new \DateTimeZone($value ['time_zone']));
            $arrivedTime = \DateTime::createFromFormat(StaticOptions::MYSQL_DATE_FORMAT, $value ['time_slot'], new \DateTimeZone($value ['time_zone']));
            $currentTimeNew = StaticOptions::getRelativeCityDateTime(array(
                                'restaurant_id' => $value ['restaurant_id']
                            ))->format(StaticOptions::MYSQL_DATE_FORMAT);
            $currentDate = StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT);
            $differenceOfTimeInMin = round(abs(strtotime($arrivedTime->format("Y-m-d H:i:s"))-strtotime($currentTimeNew))/60); 
            if(strtotime($arrivedTime->format("Y-m-d H:i:s"))> strtotime($currentTimeNew)){
            if (($differenceOfTimeInMin > 55) && ($differenceOfTimeInMin < 61)) {
                $userSmsData = array();
                $specChar = $config ['constants']['special_character'];
                $userSmsData['user_mob_no'] = $value ['phone'];
                //if(isset($data['order_id'])){
                //$userSmsData['message'] = "Your Munch Ado Pre-Paid Reservation at " . strtr($value['restaurant_name'],$specChar) . " for " . $value['reserved_seats'] . " is in one hour. Suit up!";
                //}else{
                $userSmsData['message'] = "Your reservation at " . strtr($value['restaurant_name'],$specChar) . " through Munch Ado for " . $value['reserved_seats'] . " is in an hour. Suit up!";
                //}
                if($value ['host_name']=='munchado.com'){
                //StaticOptions::sendSmsClickaTell($userSmsData,$value ['user_id']);
                }
                ########## Notification to user on first Registration ########

//                $notificationMsg='EEE! Just an hour and a half until your reservation at '.$value['restaurant_name'].'.';
//                $channel = "mymunchado_" . $value ['user_id'];
//                $notificationArray = array(
//                    "msg" => $notificationMsg,
//                    "channel" => $channel,
//                    "userId" => $value ['user_id'],
//                    "type" => 'reservation',    
//                    "restaurantId" => $value ['restaurant_id'],        
//                    'curDate' => $currentDate,
//                    'restaurant_name'=>ucfirst($value['restaurant_name']),
//                    'reservation_id'=>$value['id']
//                );
//                $notificationJsonArray = array('reservation_id'=>$value['id'],'restaurant_id' => $value ['restaurant_id'],'restaurant_name'=>ucfirst($value['restaurant_name']));
//                $response = $userNotificationModel->createPubNubNotification($notificationArray,$notificationJsonArray);        
//                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                $cronUpdate=1;
            }
            }
            if(strtotime($arrivedTime->format("Y-m-d H:i:s"))< strtotime($currentTimeNew)){
            if($differenceOfTimeInMin>120){
//                $notificationMsg="Don't forget to upload a picture of your bill to receive your Munch Ado points!";
//                $channel = "mymunchado_" . $value ['user_id'];
//                $notificationArray = array(
//                    "msg" => $notificationMsg,
//                    "channel" => $channel,
//                    "userId" => $value ['user_id'],
//                    "type" => 'reservation',    
//                    "restaurantId" => $value ['restaurant_id'],        
//                    'curDate' => $currentDate,
//                    'reservation_id'=>$value['id']
//                );
//                $notificationJsonArray = array('reservation_id'=>$value['id'],'restaurant_id' => $value ['restaurant_id'],'restaurant_name'=>ucfirst($value['restaurant_name']));
//                $response = $userNotificationModel->createPubNubNotification($notificationArray,$notificationJsonArray);        
//                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                $cronUpdate=1;
            }
            }
            if($cronUpdate==1){
                $reservationModel->updateCronReservation($value['id']);
            }
    }
}