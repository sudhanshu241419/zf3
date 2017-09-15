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
        'id','order_pass_through',
        'fname','lname','payment_receipt','created_at','phone','status','total_amount','status'
    ),
    'joins' => $joins,
    'where' => array('(user_orders.status = "rejected" OR  user_orders.status = "cancelled" OR user_orders.status = "confirmed" OR user_orders.order_type = "Takeout" OR  user_orders.order_type = "Delivery") and cronUpdate="0"'),
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
            if($differenceOfTimeInMin>=90){
                if($values['user_id']>0){ 
                $feed = array(
                    'restaurant_id' => $values['restaurant_id'],
                    'restaurant_name' => $values['restaurant_name'],
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'amount' =>$values['total_amount'],
                    'order_items' => $values['order_items'],
                    'friend_id'=>$values['user_id']
                );
                $replacementData = array('restaurant_name'=>$values['restaurant_name']);
                $otherReplacementData = array('user_name'=>ucfirst($uname));
                
                if(trim($values['order_type'])=== "Takeout"){
                 $activityFeed = addActivityFeed($feed, 15, $replacementData, $otherReplacementData,$values['restaurant_id']);      
                }
                if(trim($values['order_type'])=== "Delivery"){
                $activityFeed = addActivityFeed($feed, 14, $replacementData, $otherReplacementData,$values['restaurant_id']);       
                }
                $reservationModel->updateCronOrder($values['id']);
                }
            }
        }
        if(trim($values['status'])=='confirmed'){
            if($values['user_id']>0){
             $feed = array(
                    'restaurant_id' => $values['restaurant_id'],
                    'restaurant_name' => $values['restaurant_name'],
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'amount' =>$values['total_amount'],
                    'order_items' => $values['order_items'],
                    'friend_id'=>$values['user_id']
                );
             
                $replacementData = array('restaurant_name'=>$values['restaurant_name']);
                $otherReplacementData = array();
                $activityFeed = addActivityFeed($feed, 1, $replacementData, $otherReplacementData,$values['restaurant_id']);      
                $reservationModel->updateCronOrder($values['id']);
            }
        }
        if(trim($values['status'])=='cancelled'){
            if($values['user_id']>0){
             $feed = array(
                    'restaurant_id' => $values['restaurant_id'],
                    'restaurant_name' => $values['restaurant_name'],
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'amount' =>$values['total_amount'],
                    'order_items' => $values['order_items'],
                    'friend_id'=>$values['user_id']
                );
             
                $replacementData = array('restaurant_name'=>$values['restaurant_name']);
                $otherReplacementData = array();
                $activityFeed = addActivityFeed($feed, 3, $replacementData, $otherReplacementData,$values['restaurant_id']);
                $reservationModel->updateCronOrder($values['id']);
            }
        }
        if(trim($values['status'])=='rejected'){
            if($values['user_id']>0){ 
             $feed = array(
                    'restaurant_id' => $values['restaurant_id'],
                    'restaurant_name' => $values['restaurant_name'],
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'amount' =>$values['total_amount'],
                    'order_items' => $values['order_items'],
                    'friend_id'=>$values['user_id']
                );
             
                $replacementData = array('restaurant_name'=>$values['restaurant_name']);
                $otherReplacementData = array();
                if(trim($values['order_pass_through'])==1 && trim($values['order_type'])=="Takeout"){
                $activityFeed = addActivityFeed($feed, 65, $replacementData, $otherReplacementData,$values['restaurant_id']);    
                $reservationModel->updateCronOrder($values['id']);
                }
            }
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
                '65'=>'order',
                '66'=>'referal',
                '67'=>'referal',
                '68'=>'new_register',
                '69'=>'tips',
                '70'=>'tips',
                '71'=>'upload_photo',
                '72'=>'upload_photo'
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

