<?php

/*
  ############### How to run #################
  QUEUE=default VERBOSE=1 APPLICATION_ENV=local php worker.php > log_worker.txt &
  ##################--########################
 */

use MCommons\StaticOptions;
include_once realpath(__DIR__ . "/../")."/config/konstants.php";
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));

require_once dirname(__FILE__) . "/init.php";
StaticOptions::setServiceLocator($GLOBALS['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
require_once dirname(__FILE__) . "/UploadS3.php";
Resque::setBackend($config['constants']['redis']['host'] . ":" . $config['constants']['redis']['port']);
$item = Resque::getList("netcore");
$clevertap = new Clevertap();
$userFunctions = new \User\UserFunctions();

$userImage = new User\Model\UserRestaurantimage();
$userReview = new User\Model\UserReview();
$userOrder = new \User\Model\UserOrder();
$reservationOld = new User\Model\UserReservation();
$reservationDinein = new Restaurantdinein\Model\Restaurantdinein();
$eventtype = array("bookmark", "uncheck_bookmark", "dine_and_more", "general", "refer_friend", "check_in");


//$item[2] = '{"class":"netcore","args":[{"user_id":2852,"name":"done","email":"donenetcore@mailinator.com","identity":"donenetcore@mailinator.com","eventname":"signed_to_app","event":1,"is_register":"yes","date":"2017-06-01 14:30"}],"id":"6ff2dc492de607a253ba52b9c5f42d81"}';

if (count($item) > 0 && !empty($item)) {
    //print_r($item);
    
    foreach ($item as $key => $val) {
        $jobs = json_decode($val, true);
        Resque::pop("netcore");
        if ($jobs['class'] === 'netcore') {   
            if (isset($jobs['args'][0]['event'])) {
                $userFunctions->restaurantId = isset($jobs['args'][0]['restaurant_id']) ? $jobs['args'][0]['restaurant_id'] : 0;
                $userFunctions->userId = isset($jobs['args'][0]['user_id']) ? $jobs['args'][0]['user_id'] : 0;
                
                if($jobs['args'][0]['eventname']=="dine_and_more"){
                     $jobs['args'][0]['restaurant_story'] = $userFunctions->restaurantStory($jobs['args'][0]['restaurant_id']);
                }
               
                isRestaurantTagsDandM($jobs);
                isUserDineAndMore($jobs);
                
                ########## Start Event to upload ##################
                if($jobs['args'][0]['eventname'] == "canceltran"){  
                    redeemPoint($jobs['args'][0]);
                    unset($item[$key]);
                }
                
                
                if ($jobs['args'][0]['eventname'] == "upload_pic") {
                    $galleryId = $jobs['args'][0]['gallery_id'];
                    $imageStatus = $userImage->userImageStatus($galleryId);
                    if ($imageStatus[0]['status']==1) {
                        unset($item[$key]);
                        $data['approved_date'] = $imageStatus[0]['updated_on'];                        
                        $clevertap->uploadEvent($jobs['args'][0]);       
                        profileUpload($jobs['args'][0],"update");
                    }else{
                       netcoreQueue($jobs['args'][0]);
                    }                  
                   
                } elseif ($jobs['args'][0]['eventname'] == "review") {
                    $reviewId = $jobs['args'][0]['review_id'];
                    if(reviewEvent($reviewId, $config, $userReview, $jobs['args'][0])){
                        profileUpload($jobs['args'][0],"update");
                        unset($item[$key]);
                    }else{
                        netcoreQueue($jobs['args'][0]);
                    }
                } elseif ($jobs['args'][0]['eventname'] == "order") {
                    $orderId = $jobs['args'][0]['orderid'];
                    if(orderEvent($orderId, $config, $userOrder, $jobs['args'][0])){
                        profileUpload($jobs['args'][0],"update");
                        unset($item[$key]);
                    }else{
                       netcoreQueue($jobs['args'][0]);
                    }
                } elseif($jobs['args'][0]['eventname'] == "snag_a_spot"){
                    if($reservationDinein->getReservationStatus($jobs['args'][0]['reservation_id'])){
                        $clevertap->uploadEvent($jobs['args'][0]);
                        profileUpload($jobs['args'][0],"update");
                        unset($item[$key]);
                    }else{
                       netcoreQueue($jobs['args'][0]);
                    }
                }elseif($jobs['args'][0]['eventname'] == "reservation"){
                    $currentDate = StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $jobs['args'][0]['restaurant_id']
                    ))->format('Y-m-d h:i');    
                                      
                    if(strtotime($currentDate) >= strtotime($jobs['args'][0]['res_date_time'])){
                        $reservationStatus = $reservationOld->userReservationDetail($jobs['args'][0]['reservation_id']);  
                        
                        if($reservationStatus[0]['status']==4){
                           $clevertap->uploadEvent($jobs['args'][0]);
                           profileUpload($jobs['args'][0],"update");   
                           unset($item[$key]);
                        }else{
                            netcoreQueue($jobs['args'][0]);
                        }
                    }else{
                        netcoreQueue($jobs['args'][0]);
                    }
                    
                }elseif($jobs['args'][0]['eventname']=="signed_to_app"){
                    $jobs['args'][0]['event_date'] = $jobs['args'][0]['date'];
                    $clevertap->uploadEvent($jobs['args'][0]);
                    unset($item[$key]);
                    
                }elseif (in_array($jobs['args'][0]['eventname'], $eventtype)) {     
                    if(isset($jobs['args'][0]['registration_date'])){
                        $jobs['args'][0]['event_date'] = $jobs['args'][0]['registration_date'];
                        profileUpload($jobs['args'][0],"add");
                    }else{
                        $jobs['args'][0]['event_date'] = isset($jobs['args'][0]['date'])?$jobs['args'][0]['date']:"";
                       profileUpload($jobs['args'][0],"update");
                    }
                    $clevertap->uploadEvent($jobs['args'][0]);
                    unset($item[$key]);
                }
                
                
            } 
        }
        
    }    
}


function reviewEvent($reviewId, $config, $userReview, $data) {
    $clevertap = new Clevertap();
    $reviewStatus = $userReview->reviewStatus($reviewId);
    if ($reviewStatus) {
        $clevertap->uploadEvent($data);       
        return true;
    }
    return false;
}

function orderEvent($orderId, $config, $userOrder, $data) {
    $clevertap = new Clevertap();
    $orderStatus = $userOrder->orderStatus($orderId);
    $user_id = (isset($data['user_id']) && $data['user_id']!=0 && !empty($data['user_id']) && $data['user_id'])?$data['user_id']:false;
    
    unset($data['event'],$data['orderid']);
    
    if ($orderStatus[0]['status'] === "delivered" || $orderStatus[0]['status'] === "arrived") {
        if($user_id){
            $restaurantServer = new User\Model\RestaurantServer();
            $restaurantServer->restaurant_id = $data['restaurant_id'];
            $restaurantServer->user_id = $user_id;
            $isUserDineMore = $restaurantServer->isUserRegisterWithRestaurant();
            if($isUserDineMore[0]['id']>=1){
               $isfirstOrder = $userOrder->getTotalUserFirstOrders($user_id,$data['restaurant_id']);            
            }else{
               $isfirstOrder = $userOrder->getTotalUserFirstOrders($user_id); 
            }
            if($isfirstOrder[0]['total_order']==1){
                   $data['first_order'] = "yes";
               }else{
                   $data['first_order'] = "no";
               }
        }
        
        $clevertap->uploadEvent($data);
        
        return true;
    }
    return false;
}
function profileUpload($data,$action){
    $clevertap = new Clevertap();
    $userDetails = userdetails($data); 
    if(isset($data['user_id']) && $data['user_id'] > 0){
        $userPointsCount = new \User\Model\UserPoint();  
        
        $userPointsSum = $userPointsCount->countUserPoints($data['user_id']);        
        $redeemPoint = ($userPointsSum[0]['redeemed_points'])?$userPointsSum[0]['redeemed_points']:0;
        $userPoints = $userPointsSum[0]['points'] - $redeemPoint;              
        $eventData = [];    
        $eventData['name'] = (isset($userDetails[0]['last_name']) && !empty($userDetails[0]['last_name']))?$userDetails[0]['first_name']. " ". $userDetails[0]['last_name']:$userDetails[0]['first_name'];
        $eventData['user_id'] = $data["user_id"];
        $eventData['identity'] = $userDetails[0]['email'];
        $eventData['email'] = $userDetails[0]['email'];
        $eventData['registration_date'] =$userDetails[0]['created_at'];
        $eventData['host_url'] = (isset($data['host_url']) && !empty($data['host_url']))?$data['host_url']:PROTOCOL.SITE_URL;
        $eventData['is_register'] = "yes";
        $eventData['earned_points'] = $userPointsSum[0]['points'];
        $eventData['redeemed_points'] = $redeemPoint; 
        $eventData['earned _dollar'] = StaticOptions::convertPointToDollar($userPointsSum[0]['points']);
        $eventData['remaining_points']=$userPoints;
        $eventData['remaining_dollar']=StaticOptions::convertPointToDollar($userPoints);
        $eventData['registration_source']=$userDetails[0]['user_source'];  
        $eventData['cms_reg'] = isset($data['cms_reg'])?$data['cms_reg']:"no";
        if($action=="add"){
            $clevertap->uploadProfile($eventData);
        }else{
             $clevertap->updateProfile($eventData);
        }
        return true;
    }else{       
        $eventData['name'] = $data['name'];        
        $eventData['identity'] = $data['identity'];
        $eventData['email'] = $data['identity'];        
        $eventData['host_url'] = (isset($data['host_url']) && !empty($data['host_url']))?$data['host_url']:PROTOCOL.SITE_URL;
        $eventData['is_register'] = "no";
        $eventData['earned_points'] = 0;
        $eventData['redeemed_points'] = 0; 
        $eventData['earned _dollar'] = 0;
        $eventData['remaining_points']=0;
        $eventData['remaining_dollar']=0;
        $eventData['cms_reg'] = "no";
        $clevertap->uploadProfile($eventData);
        
        return true;
    }
    return false;
}

    function userdetails($data){
        $user = new User\Model\User();
        $joins_user [] = array(
            'name' => array(
                'ua' => 'user_account'
            ),
            'on' => 'users.id = ua.user_id',
            'columns' => array(
                "user_source"
            ),
            'type' => 'inner'
        );
        if(isset($data['user_id']) && !empty($data['user_id']) && $data['user_id']>0){
            $where = array("users.id"=>$data['user_id']);
        }else{
            $where = array("users.email"=>$data['identity']);
        }
        
        $options = array(
            "columns"=>array("id","first_name","last_name","email","created_at"),
            "where"=>$where,
            "joins"=>$joins_user
            );
        $userDetails = $user->getAUser($options); 
        return $userDetails;
    }
    
    function netcoreQueue($data){
         $token = Resque::enqueue("netcore", "netcore", $data, true);
         $status = new Resque_Job_Status($token);
         return $status->get(); // Outputs the status
    }
    
    function isUserDineAndMore(&$jobs){
        $restaurantServer = new User\Model\RestaurantServer();
        if (isset($jobs['args'][0]['restaurant_id']) && !empty($jobs['args'][0]['restaurant_id']) && $jobs['args'][0]['restaurant_id'] != NULL) {
            if (isset($jobs['args'][0]['user_id']) && !empty($jobs['args'][0]['user_id']) && $jobs['args'][0]['user_id'] != NULL) {
                $restaurantServer->user_id =  $jobs['args'][0]['user_id'];
                $restaurantServer->restaurant_id = $jobs['args'][0]['restaurant_id'];
                $restServer = $restaurantServer->isUserRegisterWithRestaurant();                         
                $jobs['args'][0]['user_dine_more'] = ($restServer[0]['id'] > 0) ? "yes" : "no";
            } else {
                $jobs['args'][0]['user_dine_more'] = "no";
            }
        }else{
            $jobs['args'][0]['user_dine_more'] = "no";
        }
        
        if($jobs['args'][0]['restaurant_dine_more']=="no"){
            $jobs['args'][0]['user_dine_more'] = "no";
        }       
        
    }
    
    
    function isRestaurantTagsDandM(&$jobs){
        
        $userFunctions = new \User\UserFunctions();
        if (isset($jobs['args'][0]['restaurant_id']) && !empty($jobs['args'][0]['restaurant_id']) && $jobs['args'][0]['restaurant_id'] != NULL) {
              $isRestDineAndMore = $userFunctions->restaurantTaged($jobs['args'][0]['restaurant_id']);
              $jobs['args'][0]['restaurant_dine_more'] = ($isRestDineAndMore) ? "yes" : "no";
        }else{
            $jobs['args'][0]['restaurant_dine_more'] = "no";
        }
    }
    
    
    function redeemPoint($data){
        $clevertap = new Clevertap();
        $userDetails = userdetails($data);
        $eventData = [];
        $userPointsCount = new \User\Model\UserPoint();        
        $userPointsSum = $userPointsCount->countUserPoints($data['user_id']);        
        $redeemPoint = ($userPointsSum[0]['redeemed_points'])?$userPointsSum[0]['redeemed_points']:0;
        $userPoints = $userPointsSum[0]['points'] - $redeemPoint; 
        $eventData['email'] = $userDetails[0]['email'];
        $eventData['earned_points'] = $userPointsSum[0]['points'];
        $eventData['redeemed_points'] = $redeemPoint; 
        $eventData['earned _dollar'] = StaticOptions::convertPointToDollar($userPointsSum[0]['points']);
        $eventData['remaining_points']=$userPoints;
        $eventData['remaining_dollar']=StaticOptions::convertPointToDollar($userPoints);
        $clevertap->updateProfile($eventData);
    }
