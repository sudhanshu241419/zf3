<?php
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
use MCommons\StaticOptions;
use Zend\Db\Sql\Predicate\Expression;
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
$tipModel = new \User\Model\UserTip();
$commonFunctiion = new \MCommons\CommonFunctions();
$currentDate = StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT);

$joins [] = array(
    'name' => array(
        'r' => 'restaurants'
    ),
    'on' => new Expression("(user_tips.restaurant_id = r.id)"),
    'columns' => array(
        'city_id','restaurant_name'
    ),
    'type' => 'inner'
);
$joins [] = array(
    'name' => array(
        'u' => 'users'
    ),
    'on' => new Expression("(user_tips.user_id = u.id)"),
    'columns' => array('user_name'=>new Expression("concat(`first_name`,' ',`last_name`)")),
    'type' => 'inner'
);
$options = array(
    'columns' => array(
        'restaurant_id',
        'user_id',
        'id','tip','status'
    ),
    'joins' => $joins,
    'where' => array('(user_tips.status = "0" OR  user_tips.status = "1") and cronUpdate="0" and DATE_FORMAT( approved_date,  "%Y-%m-%d" ) = CURDATE( ) '),
    'order'=>'id DESC'
);
$tipModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
$allTips = $tipModel->find($options)->toArray();
//pr($allTips,1);
if(count($allTips) > 0){
    foreach($allTips as $key=>$values){
        $uname =$values['user_name'];
   
        //status 0 =disapproved   
        if(trim($values['status'])=='0'){
            if($values['user_id']>0){
             $feed = array(
                    'restaurant_id' => $values['restaurant_id'],
                    'restaurant_name' => $values['restaurant_name'],
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'friend_id'=>$values['user_id']
                );
                $replacementData = array('restaurant_name'=> $values['restaurant_name']);
                $otherReplacementData = array();
                $activityFeed = addActivityFeed($feed, 70, $replacementData, $otherReplacementData,$values['restaurant_id']);      
                $tipModel->updateCronOrder($values['id']);
            }
        }
        //status 0 =approved 
        if(trim($values['status'])=='1'){
            if($values['user_id']>0){
             $feed = array(
                    'restaurant_id' => $values['restaurant_id'],
                    'restaurant_name' => $values['restaurant_name'],
                    'user_name' => ucfirst($uname),
                    'img' => array(),
                    'user_id'=>$values['user_id'],
                    'friend_id'=>$values['user_id']
                );
             
                $replacementData = array('restaurant_name'=>$values['restaurant_name']);
                $otherReplacementData = array('user_name'=>ucfirst($uname),'restaurant_name'=>$values['restaurant_name']);
                $activityFeed = addActivityFeed($feed, 69, $replacementData, $otherReplacementData,$values['restaurant_id']);
                $tipModel->updateCronOrder($values['id']);
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