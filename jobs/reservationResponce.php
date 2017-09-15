<?php
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$reservationModel = new \User\Model\UserReservation();
$commonFunctiion = new \MCommons\CommonFunctions();
$currentDate = StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT);
$joins [] = array(
    'name' => array('uo' => 'restaurants'),
    'on' => new Expression("(user_reservations.restaurant_id = uo.id)"),
    'columns' => array('city_id','restaurant_name'),
    'type' => 'inner'
);
$joins [] = array('name' => array('ci' => 'cities'),
    'on' => new Expression("(ci.id = uo.city_id)"),
    'columns' => array('time_zone'),
    'type' => 'inner'
);
$options = array('columns' => array('restaurant_id','restaurant_name','last_name','first_name',
        'id','time_slot','reserved_seats','user_id','status'
    ),
    'joins' => $joins,
    'where' => array('user_reservations.status = "3" AND (user_reservations.restaurant_comment != "" OR user_reservations.restaurant_comment != "NULL") AND cronUpdateForCancelation="0"'  )
);
$reservationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allTakeoutDelivery = $reservationModel->find($options)->toArray();
if(count($allTakeoutDelivery) > 0){
    foreach($allTakeoutDelivery as $key=>$values){
        // check wether the restaurant time is not less than delivery time.
                if($values['user_id']>0){
                    $feedDate = date('M d Y', strtotime($values ['time_slot']));
                    $feedTime = date('h:i a', strtotime($values ['time_slot']));
                    $replacementData = array('restaurant_name' => $values ['restaurant_name']);
                    $otherReplacementData = array();
                    $uname = (isset($values ['last_name']) && !empty($values ['last_name'])) ? $values ['first_name'] . " " . $values ['last_name'] : $values ['first_name'];
                    $feed = array(
                        'restaurant_id' => $values ['restaurant_id'],
                        'restaurant_name' => $values ['restaurant_name'],
                        'user_name' => ucfirst($uname),
                        'img' => array(),
                        'reservation_time' => $feedTime,
                        'reservation_date' => $feedDate,
                        'no_of_people' => $values ['reserved_seats'],
                        'friend_id'=>$values['user_id']
                    );
                $activityFeed = addActivityFeed($feed, 5, $replacementData, $otherReplacementData,$values['restaurant_id']);      
                $reservationModel->updateCronOrder($values['id']);
                }
    }
}
function getActivityFeedType($activityTypeId = false){
        $allActivityFeedType = false;
        if($activityTypeId){
            $activityFeedTypeModel = new \User\Model\ActivityFeedType();
            $activityFeedTypeModel->getDbTable()->setArrayObjectPrototype('ArrayObject');       
            $options = array('where'=>array('status'=>'1','id'=>$activityTypeId));       
            if ($activityFeedTypeModel->find($options)->toArray()) {
                $allActivityFeedType = $activityFeedTypeModel->find($options)->toArray();                 
            }
        }
        return $allActivityFeedType;
    }
    
function replaceDefineString($replacementValue=array(),$string=''){
        $message = '';
        if(!empty($replacementValue) && $string!=""){
            foreach($replacementValue as $key => $value){
                $string = str_replace('{{#'.$key.'#}}', $value,$string);                
            }
           return $string;
        }        
        return $message;
    }
    
function addActivityFeed($feed=array(), $feedType, $replacementData=array(), $otherReplacementData=array(),$restaurantId=false){
        $activityFeedType = getActivityFeedType($feedType);        
        $feedMessage = '';
        $otherFeedMessage = '';
        //$currentDate = StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT);
        
        if(isset($feed['friend_id']) && !empty($feed['friend_id'])){
            $user_id = $feed['friend_id'];
        }
        
        #######################
        $currentDate=StaticOptions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurantId
                ))->format(StaticOptions::MYSQL_DATE_FORMAT);
        #######################
        if($activityFeedType){
            ############ Get privacy setting ############
            $userActionSettings = new \User\Model\UserActionSettings();            
            $response = $userActionSettings->select(array('where'=>array('user_id'=>$user_id)));
            if($response){
            $feedPrivacy = array(
                '1'=>'order',
                '4'=>'reservation',
                '6'=>'reservation',
                '7'=>'reservation',
                '9'=>'reviews',
                '10'=>'tips',
                '11'=>'upload_photo',
                '12'=>'bookmarks',
                '13'=>'reviews',
                '16'=>'bookmarks',
                '17'=>'bookmarks',
                '20'=>'reservation',
                '21'=>'muncher_unlocked',
                '22'=>'checkin',
                '24'=>'checkin',
                '25'=>'checkin',
                '26'=>'checkin',
                '34'=>'checkin',
                '35'=>'checkin',
                '36'=>'checkin',
                '37'=>'muncher_unlocked',
                '38'=>'muncher_unlocked',
                '39'=>'muncher_unlocked',
                '40'=>'muncher_unlocked',
                '41'=>'muncher_unlocked',
                '42'=>'muncher_unlocked',
                '43'=>'muncher_unlocked',
                '44'=>'muncher_unlocked',
                '45'=>'muncher_unlocked',
                '51'=>'bookmarks',
                '52'=>'checkin',
                '53'=>'new_register',
                '54'=>'accept_friendship',
                '55'=>'bookmarks',
                '56'=>'bookmarks',
                '57'=>'reservation',
                '58'=>'reservation',
                '59'=>'reservation',
                '60'=>'reservation',
                '61'=>'referal',
                '62'=>'referal',
                '63'=>'referal',
                '64'=>'referal',
                );
            $privacyType = $feedPrivacy[$activityFeedType[0]['id']];
            $data['privacy_status'] = isset($response[0][$privacyType]) && $response[0][$privacyType]!=''?$response[0][$privacyType]:1;
            }else{
                $data['privacy_status'] = 1;
            }
            #############################################
            $feedMessage = replaceDefineString($replacementData, $activityFeedType[0]['feed_message']);
            $otherFeedMessage = replaceDefineString($otherReplacementData, $activityFeedType[0]['feed_message_others']);

            $feed['feed_for_other'] = $otherFeedMessage;
            $feed['text'] = $feedMessage;
            if( !isset($feed['event_date_time']) || empty($feed['event_date_time'])){
               $feed['event_date_time'] = $currentDate;
           }          
           $data['feed'] = json_encode($feed);
           $data['feed_type_id'] = $activityFeedType[0]['id'];
           $data['feed_for_others'] = $otherFeedMessage;
           $data['added_date_time'] = $currentDate;
           $data['event_date_time'] = $feed['event_date_time'];
           $data['user_id']= $user_id;
           $data['status'] = 1;
            $activityFeedModel = new \User\Model\ActivityFeed();                 
            if ($activityFeedModel->insert($data)) {
               return true;               
            }else{
               return false;
            }
        }else{
            return false;
        }
      
}