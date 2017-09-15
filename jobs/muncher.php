<?php
/* This file is use to assign muncher for users.
 * set service location with application envirement */
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'local'));
require_once dirname(__FILE__) . "/init.php";
use MCommons\StaticOptions;
use User\Model\User;
use User\Model\UserOrder;
use User\Model\UserReservation;
use User\Model\UserCheckin;
use User\Model\UserFriendsInvitation;
use User\Model\UserReview;
use User\Model\UserTip;
use MCommons\CommonFunctions;
StaticOptions::setServiceLocator($GLOBALS ['application']->getServiceManager());
$sl = StaticOptions::getServiceLocator();
$config = $sl->get('Config');
/* create instance of user class to get user details */
$users=new User();
/* create instance of userorder class to get user order details */
$userOrder=new UserOrder();
/* create instance of userreservation class to get user reservation details */
$userReservationModel=new UserReservation();
/* create instance of user checkin class to get user checkin details */
$userCheckinModel=new UserCheckin();
/* create instance of user invitation class to get user invitation details */
$userInvitationModel=new UserFriendsInvitation();
/* create instance of user invitation class to get user invitation details */
$userReviewModel=new UserReview();
/* create instance of user invitation class to get user invitation details */
$userTipModel=new UserTip();

$getUsers=$users->getAllUserIds();//get all user ids
$orderFunctions = new \Restaurant\OrderFunctions();
/* below implement the logic to assign muncher for user */
if(isset($getUsers) && count($getUsers)>0){
    foreach($getUsers as $key=>$userIds){
        $userId=(int) $userIds['id'];//get user id 
        $getUserOrders=$userOrder->getUserConfirmOrder($userId);//get user confirm order
        $getUserReservations=$userReservationModel->getUserReservationIds($userId);//get user confirm reservation
        $getUserCheckIn=$userCheckinModel->getUsercheckin($userId);//get user checkin
        $getUserInvitation=$userInvitationModel->getUserAllInvitation($userId);//get user checkin
        $getUserReview=$userReviewModel->getUserAllReview($userId);//get user review comments
        $getUserTip=$userTipModel->getUserAllTip($userId);//get user tip comments
        /* below are the logic to assign muncher for orders */
        if(count($getUserOrders)>0){
         $getUserDeliveryOrders=$userOrder->getUserConfirmDeliveryOrder($userId);//get user confirm Delivery order
         $getUserTakeoutOrders=$userOrder->getUserConfirmTakeoutOrder($userId);//get user confirm Takeout order
         /* below implement the logic to assign muncher for Delivery Orders */
         if(count($getUserDeliveryOrders)>0){
             foreach($getUserDeliveryOrders as $key=>$userDeliveryOrdersValue){
                 assignMuncher($userId,'Stay At Home Eater');
             }
         }
         /* below implement the logic to assign muncher for TakeOut Orders */
         if(count($getUserTakeoutOrders)>0){
             foreach($getUserTakeoutOrders as $key=>$userTakeoutOrdersValue){
                 assignMuncher($userId,'Takeout Artist');
             }
         }
         /* below implement the logic to assign muncher for Asian cuisine */
         foreach($getUserOrders as $key=>$resIds){
            $restaurantId=(int) $resIds['restaurant_id']; 
            $cusines = $orderFunctions->getResCuisineDetail($restaurantId);
            if (in_array('Asian', $cusines)) {
                    assignMuncher($userId,'Fu Munchu');
                }

                if (in_array('Gluten-Free', $cusines) || in_array('Vegetarian', $cusines) || in_array('Vegan', $cusines) || in_array('Health Food', $cusines)) {
                    assignMuncher($userId,'Health Nut');
                }
                
                if (in_array('Pizza', $cusines)) {
                    assignMuncher($userId,'The Cheesy Triangle','Pizza',$restaurantId,$resIds['id']);
                }
                
                if (in_array('Burgers', $cusines)) {
                    assignMuncher($userId,'Sir Loin','Burgers',$restaurantId,$resIds['id']);
                }
                updateMuncher($userOrder,$resIds['id']);
         }
        }
        
        /* below are the logic to assign muncher for reservations */
        if(count($getUserReservations)>0){
            foreach($getUserReservations as $key=>$reserve){
                $restaurantId=(int) $reserve['restaurant_id']; 
                $cusines = $orderFunctions->getResCuisineDetail($restaurantId);
                if (in_array('Asian', $cusines)) {
                    assignMuncher($userId,'Fu Munchu');
                }
                if (in_array('Gluten-Free', $cusines) || in_array('Vegetarian', $cusines) || in_array('Vegan', $cusines) || in_array('Health Food', $cusines)) {
                    assignMuncher($userId,'Health Nut');
                }
                if (in_array('Pizza', $cusines)) {
                    assignMuncher($userId,'The Cheesy Triangle','Pizza',$restaurantId,$reserve['id']);
                }

                if (in_array('Burgers', $cusines)) {
                    assignMuncher($userId,'Sir Loin','Burgers',$restaurantId,$reserve['id']);
                }
                assignMuncher($userId,'VIP');
                updateMuncher($userReservationModel,$reserve['id']);
            }
        }
        
        /* below are the logic to assign muncher for checkin */
        if(count($getUserCheckIn)>0){
          foreach($getUserCheckIn as $key=>$checkin){
            $restaurantId=(int) $checkin['restaurant_id']; 
                $cusines = $orderFunctions->getResCuisineDetail($restaurantId);
                if (in_array('Asian', $cusines)) {
                    assignMuncher($userId,'Fu Munchu');
                }
                if (in_array('Gluten-Free', $cusines) || in_array('Vegetarian', $cusines) || in_array('Vegan', $cusines) || in_array('Health Food', $cusines)) {
                    assignMuncher($userId,'Health Nut');
                }
                if (in_array('Pizza', $cusines)) {
                    assignMuncher($userId,'The Cheesy Triangle','Pizza',$restaurantId,$checkin['id']);
                }

                if (in_array('Burgers', $cusines)) {
                    assignMuncher($userId,'Sir Loin','Burgers',$restaurantId,$checkin['id']);
                } 
                updateMuncher($userCheckinModel,$checkin['id']);
          }  
        }
        
        /* below are the logic to assign muncher for invition user */
        if(count($getUserInvitation)>0){
           foreach($getUserInvitation as $key=>$invite){
             assignMuncher($userId,'Munch Maven'); 
             updateMuncher($userInvitationModel,$invite['id']);
           } 
        }
        
        /* below are the logic to assign muncher for review user */
        if(count($getUserReview)>0){
           foreach($getUserReview as $key=>$review){
             assignMuncher($userId,'Food Pundit'); 
             updateMuncher($userReviewModel,$review['id']);
           } 
        }
        
        /* below are the logic to assign muncher for tip user */
        if(count($getUserTip)>0){
           foreach($getUserTip as $key=>$tip){
             assignMuncher($userId,'Food Pundit'); 
             updateMuncher($userTipModel,$tip['id']);
           } 
        }
        
    }
}
              
/* this function use to assign Asian muncher */

function assignMuncher($user=false,$cuisine=false,$menuType=false,$res=false,$orderid=false){
    switch ($cuisine){
    case 'Fu Munchu':
        assignFuMunchuMuncher($user,'fu_munchu');
    break;
    case 'Food Pundit':
        assignFoodPunditMuncher($user,'food_pundit');
    break;
    case 'Health Nut':
        assignHealthNutMuncher($user,'health_nut');
    break;
    case 'Sir Loin':
        assignSirLoinMuncher($user,'sir_loin',$menuType,$res,$orderid);
    break;
    case 'VIP':
        assignVipMuncher($user,'vip');
    break;
    case 'Munch Maven':
        assignMunchMavenMuncher($user,'munch_maven');
    break;
    case 'The Cheesy Triangle':
        assignTheCheesyTriangleMuncher($user,'cheesy_triangle',$menuType,$res,$orderid);
    break;
    case 'Stay At Home Eater':
        assignStayAtHomeEaterMuncher($user,'home_eater');
    break;
    case 'Takeout Artist':
        assignTakeoutArtistMuncher($user,'takeout_artist');
    break;
    }
}

function assignFuMunchuMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}

function assignFoodPunditMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}
function assignHealthNutMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}
function assignSirLoinMuncher($user=false,$type=false,$menuType=false,$res=false,$orderid=false){
    $cuisines = new \Restaurant\Model\MasterCuisines();
    $menuCuisines = new \Restaurant\Model\MenuCuisine();
    $userOrderDetailModel = new \User\Model\UserOrderDetail();
    $getCuisineId=$cuisines->getCuisineId($menuType);
    $cuisineId =(int) $getCuisineId['id'];
    $menuIds=$menuCuisines->getMenuIds($cuisineId);
    $getUserOrdersItem=$userOrderDetailModel->getUserOrderItemId($orderid);
    if(count($getUserOrdersItem)>0){
        foreach($getUserOrdersItem as $key=>$items){
            if(in_array($items['item_id'], $menuIds)){
               addUpdateUserAvatar($user,$type); 
            }
        }
    }
}
function assignVipMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}
function assignMunchMavenMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}
function assignTheCheesyTriangleMuncher($user=false,$type=false,$menuType=false,$res=false,$orderid=false){
    $cuisines = new \Restaurant\Model\MasterCuisines();
    $menuCuisines = new \Restaurant\Model\MenuCuisine();
    $userOrderDetailModel = new \User\Model\UserOrderDetail();
    $getCuisineId=$cuisines->getCuisineId($menuType);
    $cuisineId =(int) $getCuisineId['id'];
    $menuIds=$menuCuisines->getMenuIds($cuisineId);
    $getUserOrdersItem=$userOrderDetailModel->getUserOrderItemId($orderid);
    if(count($getUserOrdersItem)>0){
        foreach($getUserOrdersItem as $key=>$items){
            if(in_array($items['item_id'], $menuIds)){
               addUpdateUserAvatar($user,$type); 
            }
        }
    }
 
}
function assignStayAtHomeEaterMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}
function assignTakeoutArtistMuncher($user=false,$type=false){
    addUpdateUserAvatar($user,$type);
}

function addUpdateUserAvatar($user=false,$type=false){
        $userId =(int) $user;
        if(!empty($type) ){ 
            $avatar = new \User\Model\Avatar();
            $commonFunctions = new CommonFunctions();
            $options = array('columns'=>array('id','name','avatar_image','message','action','action_number'),'where'=>array('status'=>1,'type'=>$type));
            $avatarTypeArray = $avatar->find($options)->toArray();
             if( $avatarTypeArray ){
                 $unlocked = 0;
                 $remaining = 0;
                 $avatarId = $avatarTypeArray[0]['id'];
                 $action_number = $avatarTypeArray[0]['action_number'];
                 //pr($avatarTypeArray,true);
                 $userAvatar = new \User\Model\UserAvatar();
                 $options = array('columns'=>array('id','action_count','total_earned'),'where'=>array('user_id'=>$userId,'avatar_id'=>$avatarId));
                 $userAvatarDetail = $userAvatar->find($options)->toArray(); 
                 $currentDate = StaticOptions::getDateTime()->format(StaticOptions::MYSQL_DATE_FORMAT);
                 if(!empty($userAvatarDetail[0])){      // update existing avatar of user
                     $updateData = array();
                     $total_action = $userAvatarDetail[0]['action_count']+1;
                     $updateData['action_count'] = $total_action;
                     if ($total_action % $action_number == 0) {
                        $updateData['total_earned'] = $userAvatarDetail[0]['total_earned']+1;
                        $unlocked = 1;
                     }
                     else{
                         $remaining = $action_number - ($total_action / $action_number);
                     }
                     $updateData['date_earned'] = $currentDate;
                     $userAvatar->id = $userAvatarDetail[0]['id'];
                     $userAvatar->insert($updateData);
                 }else{ // add new record
                     $insertData = array();
                     $insertData['user_id'] = $userId;
                     $insertData['avatar_id'] = $avatarId;
                     $insertData['action_count'] = 1;
                     $insertData['date_earned'] = $currentDate;
                     $insertData['total_earned'] = 0;
                     $insertData['status']= 1;
                     $userAvatar->insert($insertData);                     
                 }
                 //return (array('unlocked'=>$unlocked, 'remaining'=> $remaining));
             }         
        }
    }
    
    function updateMuncher($object=false,$id=false){
        $data = array ('assignMuncher' => 1);
        $object->id=(int) $id;
        return $object->updateMuncher($data);
    }

die('Muncher has been assign to user'."\n");