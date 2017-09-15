<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticOptions;
use User\UserFunctions;
use User\Model\UserCheckin;
use User\Model\CheckinImages;
use Zend\Http\PhpEnvironment\Request;
use Restaurant\Model\Restaurant;
use User\Model\UserFriends;
use User\Functions\UserFunctions;

class UserCheckinController extends AbstractRestfulController {
    public $earnPoints = 0;
    public function create($data) {
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        if ($isLoggedIn) {
            $userId = $session->getUserId();
        } else {
            throw new \Exception('User detail not found', 404);
        }

        if (!isset($data['restaurant_id'])) {
            throw new \Exception('Restaurant detail is not valid', 404);
        }
        $locationData = $session->getUserDetail('selected_location', []);
        $currentDate = $userFunctions->userCityTimeZone($locationData);

        $userCheckin = new UserCheckin();
        $data1['created_at'] = $currentDate;
        $data1['user_id'] = $userId;
        $data1['restaurant_id'] = $data['restaurant_id'];
        $data1['message'] = isset($data['message']) ? $data['message'] : '';
        $insertedId = $userCheckin->insert($data1);

        $restaurantModel = $this->getServiceLocator(Restaurant::class);
        $restaurantDetailOption = array('columns' => array('rest_code', 'restaurant_name'), 'where' => array('id' => $data ['restaurant_id']));
        $restDetail = $restaurantModel->findRestaurant($restaurantDetailOption)->toArray();

        $userModel = $this->getServiceLocator(\User\Model\User::class);
        $userDetailOption = array('columns' => array('first_name', 'last_name','email'), 'where' => array('id' => $userId));
        $userDetail = $userModel->getUser($userDetailOption);
        $userName = (isset($userDetail['last_name']) && !empty($userDetail['last_name'])) ? $userDetail['first_name'] . " " . $userDetail['last_name'] : $userDetail['first_name'];
        $files = '';
        $request = new Request ();
        $files = $request->getFiles();
        $fileCount = count($files);
        $imageForFeed = [];
        $data2['image_path'] = '';

        if (!empty($files) && $fileCount > 0) {
            $restaurantDetailOption = array('columns' => array('rest_code'), 'where' => array('id' => $data ['restaurant_id']));
            $restCode = $restaurantModel->findRestaurant($restaurantDetailOption)->toArray();
            $response = StaticOptions::uploadUserImages($files, APP_PUBLIC_PATH, USER_IMAGE_UPLOAD . strtolower($restCode['rest_code']) . DS . 'checkin' . DS);

            if (!empty($response)) {
                $checkinImages = new CheckinImages();
                foreach ($response as $key => $val) {
                    $data2['checkin_id'] = $insertedId;
                    $data2['status'] = 1;
                    $data2['image_path'] = $val['path'];
                    $arr_img_path = explode('/', $val['path']);
                    $length = count($arr_img_path);
                    $data2['image_name'] = $arr_img_path [$length - 1]; //
                    $userFunctions->userId = $userId;
                    $userFunctions->sweepstakesDuplicatImage($data ['restaurant_id'], $currentDate, 'checkin');
                    $data2['sweepstakes_status_winner'] = 0;
                    $addImageResponse = $checkinImages->insert($data2);
                    $imageForFeed[] = "user_images" . DS . strtolower($restCode['rest_code']) . DS . 'checkin' . DS . $data2['image_name'];
                }
            }
        }

        #   Add activity feed data   #
        $myFriend = '';
        $friendInfo = array();
        $friendInfo1 = array();
        $myFriendIds = '';
        if(isset($data['friend']) && is_string($data['friend'])&& !empty($data['friend'])){
            $data['friend'] = json_decode($data['friend'],true);
        }
            if (isset($data['friend']) && !empty($data['friend'])) {
            $friendId = '';
            $dataFriendKey = $this->removeDuplicateArray($data['friend']);
            $i = 1;
            foreach ($data['friend'] as $fkey => $fvalue) {
                if (isset($fvalue['name'])) {
                    $myFriend .= $fvalue['name'] . ", ";
                    $friendInfo1['name'] = $fvalue['name'];
                }
                if (isset($fvalue['id'])) {
                    $myFriendIds.= $fvalue['id'] . ", ";
                    $friendInfo1['id'] = $fvalue['id'];
                }
                if ($i == count($dataFriendKey)) {
                    $friendInfo[] = $friendInfo1;
                    $i = 0;
                }
                $i++;
            }
            if (!empty($myFriend)) {
                $myFriend = substr($myFriend, 0, -2);
            }
        }
        $myMenu = '';
        $menuInfo = array();
        $menuInfo1 = array();
        $menuIds = array();
        $myMenuIds = '';
        if(isset($data['menu']) && is_string($data['menu'])&& !empty($data['menu'])){
            $data['menu'] = json_decode($data['menu'],true);
        }
        if (isset($data['menu']) && !empty($data['menu'])) {
            $menuName = '';
            $dataMenuKey = $this->removeDuplicateArray($data['menu']);
            $i = 1;
            foreach ($data['menu'] as $fkey => $fvalue) {
                if (isset($fvalue['name'])) {
                    $myMenu .= $fvalue['name'] . ", ";
                    $menuInfo1['name'] = $fvalue['name'];
                }
                if (isset($fvalue['id'])) {
                    $myMenuIds .= $fvalue['id'] . ", ";
                    $menuInfo1['id'] = $fvalue['id'];
                    $menuIds[] = $fvalue['id'];
                }

                if ($i == count($dataMenuKey)) {
                    $menuInfo[] = $menuInfo1;
                    $i = 0;
                }
                $i++;
            }
            if (!empty($myMenu)) {
                $myMenu = substr($myMenu, 0, -2);
            }
        }

//            $mail = new Message();
//            $f='';
//            foreach($files as $key =>$file){
//                $f .= "Type =".$file['type']."|| Name=".$file['name']."||tmp_name".$file['tmp_name'];
//            }
//            
//            $mail->setBody($f.'fri=='.$myFriendIds.'***menu=='.$myMenuIds);
//            $mail->setFrom('test@gmail.com', 'file');
//            $mail->setSubject('checkin');
//            $mail->setTo('deepak.soni1@kelltontech.com');
//            if($mail->Sendmail()){
//                //echo "true";
//            }else{
//                //echo "false";
//            }

        $messageType = '';
        
        $resName = '';
        $cuisineType = '';
        $getCheckInMessage = "";
        $commonFunctiion = new \MCommons\CommonFunctions();
        $checkMessage = isset($data['message']) ? $data['message'] : '';
        $checkMessage = ($checkMessage != '') ? str_replace("\n", "", $checkMessage) : '';
        if (!empty($files) && $fileCount > 0 && !empty($data['menu']) && !empty($myFriend)) {
            $checkInWith = "photo_menu_friend";
            $replacementData = array('friends' => $myFriend, 'menu_item' => $myMenu, 'restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('menu_item' => $myMenu, 'user_name' => ucfirst($userName), 'friends' => $myFriend, 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => $imageForFeed,
                'tip' => NULL,
                'friendinfo' => $friendInfo,
                'menuinfo' => $menuInfo,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 36, $replacementData, $otherReplacementData);
            //$message = "You checked in at " . $restDetail['restaurant_name'] . " with " . $myFriend . " while ordering the " . $myMenu . " and sharing a pic.";
            $message ="You and your friend(s) posted a pic and shared what you were eating during your last check in at " . $restDetail['restaurant_name'] . ".";
            
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinmenuphotofriend', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkMenuPhotoFriend');
                $userFunctions->givePoints($points, $userId, $message);
                $messageType = 'Restaurant';
                $this->earnPoints = 7;
                $resName = $restDetail['restaurant_name'];
                $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
                $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            }
            //$this->userCheckingNotification('checkMenuPhotoFriend',$notResD,$notUserD,$menuInfo,$friendInfo,$myMenu,$myFriend);
            $getCheckInMessage = "You checked in at " . $restDetail['restaurant_name'] . " with " . $myFriend . " while ordering the " . $myMenu . " and sharing a pic. Way to multi-task!";
        } elseif (!empty($files) && $fileCount > 0 && !empty($data['menu'])) {
            $checkInWith = "photo_menu";
            $replacementData = array('menu_item' => $myMenu, 'restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('menu_item' => $myMenu, 'user_name' => ucfirst($userName), 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => $imageForFeed,
                'tip' => NULL,
                'menuinfo' => $menuInfo,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 34, $replacementData, $otherReplacementData);
            //$message = "You posted a pic to your check in at " . $restDetail['restaurant_name'] . " where you ordered the " . $myMenu . ".";
            $message = "You shared what you were eating and a picture during your check in at " . $restDetail['restaurant_name'] . " with the Munch Ado app.";
            
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinmenuphoto', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkMenuPhoto');
                $userFunctions->givePoints($points, $userId, $message);
                $messageType = 'Restaurant';
                $this->earnPoints = 7;
                $resName = $restDetail['restaurant_name'];
                $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
                $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            }
            //$this->userCheckingNotification('checkMenuPhoto',$notResD,$notUserD,$menuInfo,'',$myMenu);
            $getCheckInMessage = "You checked in at " . $restDetail['restaurant_name'] . ", had the " . $myMenu . " and posted a pic. Looks good!";
        } elseif (!empty($files) && $fileCount > 0 && !empty($myFriend)) {
            $checkInWith = "photo_friend";
            $replacementData = array('friends' => $myFriend, 'restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('friends' => $myFriend, 'user_name' => ucfirst($userName), 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => $imageForFeed,
                'tip' => NULL,
                'friendinfo' => $friendInfo,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 52, $replacementData, $otherReplacementData);
            $message ="You and your friend(s) posted a picture during your check in at " . $restDetail['restaurant_name'] . ".";
           
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinphotofriend', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkFriendPhoto');
                $userFunctions->givePoints($points, $userId, $message);
                $this->earnPoints = 7;
            }
            $messageType = 'Restaurant';            
            $resName = $restDetail['restaurant_name'];
            $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
            $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            //$this->userCheckingNotification('checkFriendPhoto', $notResD, $notUserD, '', $friendInfo, '', $myFriend);
            $getCheckInMessage = "You and " . $myFriend . " checked in at " . $restDetail['restaurant_name'] . " and posted a pic. " . $restDetail['restaurant_name'] . " looks so different through your lens.";
        } elseif (!empty($myFriend) && !empty($data['menu'])) {
            $checkInWith = "friend_menu";
            $replacementData = array('menu_item' => $myMenu, 'restaurant_name' => $restDetail['restaurant_name'], 'friends' => $myFriend);
            $otherReplacementData = array('menu_item' => $myMenu, 'user_name' => ucfirst($userName), 'friends' => $myFriend, 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => $imageForFeed,
                'tip' => NULL,
                'friendinfo' => $friendInfo,
                'menuinfo' => $menuInfo,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 35, $replacementData, $otherReplacementData);
           // $message = "You checked in at " . $restDetail['restaurant_name'] . " with " . $myFriend . " and ordered the " . $myMenu . ".";
            $message = "You and your friend(s) let everyone know what you devoured during your check in at " . $restDetail['restaurant_name'] . ".";
            
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinmenufriend', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkMenuFriend');
                $userFunctions->givePoints($points, $userId, $message);
                $this->earnPoints = 7;
            }
            $messageType = 'Restaurant';
            
            $resName = $restDetail['restaurant_name'];
            $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
            $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            //$this->userCheckingNotification('checkMenuFriend', $notResD, $notUserD, $menuInfo, $friendInfo, $myMenu, $myFriend);
            $getCheckInMessage = "You checked in at " . $restDetail['restaurant_name'] . " with " . $myFriend . " and ordered the " . $myMenu . ". Good get.";
        } elseif (!empty($files) && $fileCount > 0) {
            $checkInWith = "photo";
            $replacementData = array('restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('user_name' => ucfirst($userName), 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => $imageForFeed,
                'tip' => NULL,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 25, $replacementData, $otherReplacementData);
            $message = "You shared a picture on your check in at " . $restDetail['restaurant_name'] . ".";
            
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinphoto', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkPhoto');
                $userFunctions->givePoints($points, $userId, $message);
                $this->earnPoints = 5;
            }
            $messageType = 'Restaurant';
            
            $resName = $restDetail['restaurant_name'];
            $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
            $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            //$this->userCheckingNotification('checkPhoto', $notResD, $notUserD);
            $getCheckInMessage = "You posted a picture of your check in at " . $restDetail['restaurant_name'] . ". It's got a certain je ne sais quoi.";
        } elseif (isset($data['menu']) && !empty($data['menu'])) {
            $checkInWith = "menu";
            $replacementData = array('menu_item' => $myMenu, 'restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('menu_item' => $myMenu, 'user_name' => ucfirst($userName), 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'user_name' => ucfirst($userDetail['first_name']),
                'img' => array(),
                'tip' => NULL,
                'menuinfo' => $menuInfo,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 24, $replacementData, $otherReplacementData);
            $message = "You let everyone know what you ate during one of your check ins at " . $restDetail['restaurant_name'] . ".";
            
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinmenu', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkMenu');
                $userFunctions->givePoints($points, $userId, $message);
                $this->earnPoints = 3;
            }
            $messageType = 'Restaurant';
            
            $resName = $restDetail['restaurant_name'];
            $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
            $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            //$this->userCheckingNotification('checkMenu', $notResD, $notUserD, $menuInfo, '', $myMenu);
            $getCheckInMessage = "You checked in at " . $restDetail['restaurant_name'] . " and ordered the ".$myMenu.". Yum!";
        } elseif (!empty($myFriend)) {
            $checkInWith = "checkin_friend";
            $replacementData = array('friends' => $myFriend, 'restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('user_name' => ucfirst($userName), 'friends' => $myFriend, 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => array(),
                'tip' => NULL,
                'friendinfo' => $friendInfo,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 26, $replacementData, $otherReplacementData);
            $message = "You checked in at " . $restDetail['restaurant_name'] . " with your friend(s) using the Munch Ado app.";
            
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckinfriend', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkFriend');
                $userFunctions->givePoints($points, $userId, $message);
                $this->earnPoints = 3;
            }
            $messageType = 'Restaurant';
            
            $resName = $restDetail['restaurant_name'];
            $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
            $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            //$this->userCheckingNotification('checkFriend', $notResD, $notUserD, '', $friendInfo, $myFriend);
            $getCheckInMessage = "You and " . $myFriend . " checked in at " . $restDetail['restaurant_name'] . ". Another successful social outing!";
        } else {
            $checkInWith = "checkin";
            $replacementData = array('restaurant_name' => $restDetail['restaurant_name']);
            $otherReplacementData = array('user_name' => ucfirst($userName), 'restaurant_name' => $restDetail['restaurant_name']);
            $feed = array(
                'restaurant_id' => $data ['restaurant_id'],
                'restaurant_name' => $restDetail['restaurant_name'],
                'user_name' => ucfirst($userName),
                'restaurant_code' => strtolower($restDetail['rest_code']),
                'img' => array(),
                'tip' => NULL,
                'checkinmessage' => $checkMessage
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 22, $replacementData, $otherReplacementData);
            $message = "You checked in at " . $restDetail['restaurant_name'] . " with the Munch Ado app.";
                       
            if (!$this->checkinAwardsPoint($data ['restaurant_id'], 'awardscheckin', $userId, $message)) {
                $points = $userFunctions->getAllocatedPoints('checkIn');
                $userFunctions->givePoints($points, $userId, $message);
                $this->earnPoints = 2;
            }

            $messageType = 'Restaurant';
            
            $resName = $restDetail['restaurant_name'];
            $notResD = array('restaurant_name' => $resName, 'restaurant_id' => $data ['restaurant_id']);
            $notUserD = array('user_name' => $userName, 'user_id' => $userId);
            //$this->userCheckingNotification('checkIn', $notResD, $notUserD);
            $getCheckInMessage = "You're on the Munch Ado map at " . $restDetail['restaurant_name'] . ". We’ve got your back.";
        }

        ###############################
        # Assign muncher #  
//            $messageTypeCuisine='';
//            $cusinesArray=array();
//            $messageTypePlace='';
//            $restaurantId=(int) $data ['restaurant_id'];
//            $orderFunctions = new \Restaurant\OrderFunctions();
//            $featureRestaurent = $orderFunctions->getResFeatureDetailOption($restaurantId);
//            $cusines = $orderFunctions->getRandRestaurentCuisineDetail($restaurantId); 
//            if (count($cusines) > 0) {
//            $messageTypeCuisine = 'Cuisine';
//            $cusinesArray=$cusines;
//            }
//            if (count($featureRestaurent) > 0) {
//            $messageTypePlace = 'Place';
//            $cuisineType = $featureRestaurent;
//            }
        //$getExistCheckin=$this->getExistCheckin($userId,$data ['restaurant_id']);
        //$getCheckInMessage=$this->getCheckinMessage($getExistCheckin,$earnPoints,$resName,$messageTypeCuisine,$cuisineType,$messageTypePlace,$cusinesArray);
        //$getCheckInMessage = "You're on the Munch Ado map.";
//            $friendModel = new UserFriends ();
//            $friendList = $friendModel->getCheckinUserFriendList($userId);
//            $lastLoginingFriends=array();
//            if(count($friendList)>0){
//                foreach($friendList as $key=>$friendData){
//                    $friendLastLogin=$friendData['last_login'];
//                    $diff=round(abs(strtotime($friendLastLogin)-strtotime($currentDate))/60/60);
//                    if($diff<=5){
//                     $lastLoginingFriends[]['name']=$friendData['first_name'];   
//                    }
//                }
//            }
//            $lastLoginMessage='';
//            if(count($lastLoginingFriends)>0){
//                $lastLoginMessage=$this->userNameExplode(count($lastLoginingFriends),$lastLoginingFriends);
//            }
        ##################
        $restaurantFunctions = new \Restaurant\RestaurantDetailsFunctions();
        $restuarantAddress = $restaurantFunctions->restaurantAddress($data['restaurant_id']);
        $salesData['owner_email'] = 'no-reply@munchado.com';
        $salesData['email'] = $userDetail['email'];    
        $salesData['restaurant_name'] = $restDetail['restaurant_name']; 
        $salesData['restaurant_id'] = $data ['restaurant_id']; 
        $salesData['value']=$this->earnPoints;
        $salesData['description'] = "checked_in";
        $salesData['contact_ext_event_type'] = "OTHER"; 
        $salesData['location'] = $restuarantAddress;
        $salesData['identifier']="event";              

        //$userFunctions->createQueue($salesData,'Salesmanago');
        
        if ($insertedId) {
             $cleverTap = array(
                "user_id" => $userId,                        
                "name" => $userDetail['first_name'],
                "email" => $userDetail['email'],
                "identity"=>$userDetail['email'],
                "restaurant_name" => $restDetail['restaurant_name'],
                "restaurant_id" => $data['restaurant_id'],
                "eventname" => "check_in",
                "earned_points" => $this->earnPoints,
                "check_in_with"=>$checkInWith,
                "is_register" => "yes",
                "date" => $currentDate,                                     
                "event"=>1,                        
             );
             $userFunctions->createQueue($cleverTap, 'clevertap');
            return array('result' => true, 'message' => $getCheckInMessage, 'lastloginfr' => '', 'image_path' => $data2['image_path']);
        } else {
            return array('result' => false);
        }
    }

    private function checkinAwardsPoint($restaurantId, $checkinType, $userId, $message) {
        $userFunctions = new UserFunctions();
        $userFunctions->userId = $userId;
        $userFunctions->restaurantId = $restaurantId;
        if (!$userFunctions->isRegisterWithRestaurant($userId)) {
            $points = $userFunctions->getAllocatedPoints($checkinType);
            $this->earnPoints = $points['points'];
            $userFunctions->givePoints($points, $userId, $message);
            return true;
        }
        return false;
    }

    private function userCheckingNotification($key, $restD = false, $userD = false, $menuD = false, $friendD = false, $commaValues = false, $commaValuesFri = false) {
        $notification = array('checkIn' => 1, 'checkFriend' => 2, 'checkMenu' => 3, 'checkPhoto' => 4, 'checkMenuFriend' => 5, 'checkMenuPhoto' => 6, 'checkMenuPhotoFriend' => 7, 'checkFriendPhoto' => 8);
        $userNotificationModel = new \User\Model\UserNotification();
        switch ($notification[$key]) {
            case 1:
                $notificationMsg = 'You’re on the Munch Ado map at ' . ucfirst($restD ['restaurant_name']) . 'with the Munch Ado app.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT)
                );
                $notificationJsonArray = array('user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
                break;
            case 2:
                $notificationMsg = 'You checked in with your friend(s) using the Munch Ado app.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'friendinfo' => $friendD
                );
                $notificationJsonArray = array('friendinfo' => $friendD, 'user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
            case 3:
                $notificationMsg = 'You let everyone know what you ate at one of your check ins.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'menuinfo' => $menuD
                );
                $notificationJsonArray = array('menuinfo' => $menuD, 'user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
            case 4:
                $notificationMsg = 'You shared a picture on your check in.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT)
                );
                $notificationJsonArray = array('user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
            case 5:
                $notificationMsg = 'You and your friend(s) let everyone know what you devoured during your check in.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'menuinfo' => $menuD,
                    'friendinfo' => $friendD
                );
                $notificationJsonArray = array('menuinfo' => $menuD, 'friendinfo' => $friendD, 'user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
            case 6:
                $notificationMsg = 'You shared what you were eating and a picture during your check in with the Munch Ado app.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'menuinfo' => $menuD
                );
                $notificationJsonArray = array('menuinfo' => $menuD, 'user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
            case 7:
                $notificationMsg = 'You and your friend(s) posted a pic and shared what you were eating during your last check in.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'menuinfo' => $menuD,
                    'friendinfo' => $friendD
                );
                $notificationJsonArray = array('friendinfo' => $friendD, 'menuinfo' => $menuD, 'user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
            case 8:
                $notificationMsg = 'You and your friend(s) posted a picture during your check in.';
                $channel = "mymunchado_" . $userD['user_id'];
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $userD['user_id'],
                    'username' => ucfirst($userD['user_name']),
                    "type" => 'checkin',
                    "restaurantId" => $restD ['restaurant_id'],
                    "restaurant_name" => ucfirst($restD ['restaurant_name']),
                    'curDate' => StaticOptions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restD ['restaurant_id']
                    ))->format(StaticOptions::MYSQL_DATE_FORMAT),
                    'friendinfo' => $friendD
                );
                $notificationJsonArray = array('friendinfo' => $friendD, 'user_id' => $userD['user_id'], 'username' => ucfirst($userD['user_name']), 'restaurant_id' => $restD ['restaurant_id'], 'restaurant_name' => ucfirst($restD ['restaurant_name']), 'type' => '11');
                $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);

                break;
        }
    }

    private function removeDuplicateArray($listArr = false) {
        $existKey = array();
        foreach ($listArr as $row) {
            foreach ($row as $key => $val) {
                if (!in_array($key, $existKey)) {
                    $existKey[] = $key;
                }
            }
        }
        return $existKey;
    }

    private function userNameExplode($nameListCount = false, $nameList = false) {
        $message = 'Great Minds think alike! ';
        if ($nameListCount > 0) {
            if ($nameListCount == 1) {
                $message.=$nameList[0]['name'] . ' was just here time ago!';
            } else if ($nameListCount == 2) {
                $message.=$nameList[0]['name'] . ' and ' . $nameList[1]['name'] . ' was just here time ago!';
            } else if ($nameListCount == 3) {
                $message.=$nameList[0]['name'] . ',' . $nameList[1]['name'] . ' and ' . $nameList[2]['name'] . ' was just here time ago!';
            } else {
                $message.=$nameList[0]['name'] . ',' . $nameList[1]['name'] . ',' . $nameList[2]['name'] . ' and more was just here time ago!';
            }
        }
        return $message;
    }

    /*
     * This function is use to check either user has already checkin or not */

    public function getExistCheckin($userId = false, $restaurant = false) {
        $userCheckin = new UserCheckin();
        $options['columns'] = array('id');
        $options['where'] = array('user_id' => $userId, 'restaurant_id' => $restaurant);
        $userCheckin->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $allCheckin = $userCheckin->find($options)->toArray();
        if (count($allCheckin) > 1) {
            return '2';
        } else {
            return '1';
        }
    }

    public function getCheckinMessage($getExistCheckin, $earnPoints, $resName, $messageType, $restaurantFeature, $placeType = false, $cusinesArray) {
        $message = '';
        $menuCuisine = '';
        $restaurantFeatureOption = '';
        if ($getExistCheckin == '1') {
            $message.="You just earned " . $earnPoints . " points for checking in at " . $resName . ", don't spend them all in one place unless it's at Munch Ado. Obviously.\n\n";
        } else if ($getExistCheckin > 1) {
            $message.="Guess who's back, back again. It's you, at " . $resName . "!\n\n";
        }
        $menuCuisine = $this->explodeMenuCuisine(count($cusinesArray), $cusinesArray);
        $restaurantFeatureOption = $this->explodeRestaurantFeature(count($restaurantFeature), $restaurantFeature);
        if ($messageType == 'Cuisine') {
            $message.="Hey, it's a " . $menuCuisine . " restaurant. Way to expand your palate!\n\n";
        }
        if ($placeType == 'Place') {
            $message.="This place has " . $restaurantFeatureOption . "! Do what you got to do.\n\n";
        }
        return $message;
    }

    private function explodeMenuCuisine($nameListCount = false, $nameList = false) {
        $message = '';
        if ($nameListCount > 0) {
            if ($nameListCount == 1) {
                $message.=$nameList[0]['cuisine'];
            } else if ($nameListCount == 2) {
                $message.=$nameList[0]['cuisine'] . ' and ' . $nameList[1]['cuisine'] . '';
            } else if ($nameListCount == 3) {
                $message.=$nameList[0]['cuisine'] . ', ' . $nameList[1]['cuisine'] . ' and ' . $nameList[2]['cuisine'] . '';
            } else if ($nameListCount == 4) {
                $message.=$nameList[0]['cuisine'] . ', ' . $nameList[1]['cuisine'] . ', ' . $nameList[2]['cuisine'] . ' and ' . $nameList[3]['cuisine'] . '';
            } else {
                $message.=$nameList[0]['cuisine'] . ', ' . $nameList[1]['cuisine'] . ', ' . $nameList[2]['cuisine'] . ', ' . $nameList[3]['cuisine'] . ' and ' . $nameList[4]['cuisine'] . '';
            }
        }
        return $message;
    }

    private function explodeRestaurantFeature($nameListCount = false, $nameList = false) {
        $message = '';
        if ($nameListCount > 0) {
            if ($nameListCount == 1) {
                $message.=$nameList[0]['features'] . '';
            } else if ($nameListCount == 2) {
                $message.=$nameList[0]['features'] . ' and ' . $nameList[1]['features'] . '';
            } else if ($nameListCount == 3) {
                $message.=$nameList[0]['features'] . ', ' . $nameList[1]['features'] . ' and ' . $nameList[2]['features'] . '';
            } else if ($nameListCount == 4) {
                $message.=$nameList[0]['features'] . ', ' . $nameList[1]['features'] . ', ' . $nameList[2]['features'] . ' and ' . $nameList[3]['features'] . '';
            } else {
                $message.=$nameList[0]['features'] . ', ' . $nameList[1]['features'] . ', ' . $nameList[2]['features'] . ', ' . $nameList[3]['features'] . ' and ' . $nameList[4]['features'] . '';
            }
        }
        return $message;
    }

    public function getList() {
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        $friendId = $this->getQueryParams('friendid', false);
        if ($isLoggedIn) {
            $userId = $session->getUserId();
        } else {
            throw new \Exception('User detail not found', 404);
        }
        if ($friendId) {
            $userId = $friendId;
        }
        $userCheckin = new UserCheckin();
        $checkImages = new CheckinImages();
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $page = $this->getQueryParams('page', 1);
        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }
        $userCheckin->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $joins = array();
        $joins [] = array(
            'name' => 'restaurants',
            'on' => 'restaurants.id = user_checkin.restaurant_id',
            'columns' => array(
                'restaurant_name', 'address',
                'latitude',
                'longitude',
                'restaurant_image_name',
                'rest_code',
                'is_restaurant_exists' => new \Zend\Db\Sql\Expression('if(inactive = 1 or closed = 1,"No","Yes")'),
            ),
            'type' => 'left'
        );
        $options = array('where' => array('user_id' => $userId), 'joins' => $joins, 'order' => array(
                'user_checkin.id' => 'desc'
        ));
        $responses = array();
        $userCheckinDetail = false;
        if ($userCheckin->find($options)->toArray()) {
            $userCheckinDetail = $userCheckin->find($options)->toArray();
        }
        
        if($friendId){
            $friendsCheckin = $this->getFriendCheckIn($userId, $offset, $limit);
            if(!empty($friendsCheckin)){
                $getTotalFriendCheckIn = $this->getTotalFriendCheckIn($userId);
                $responses['friends_checkin'] = $friendsCheckin;
                $responses['total_friends_checkin'] = count($getTotalFriendCheckIn);
            }else{
                $responses['friends_checkin'] = array();
                $responses['total_friends_checkin'] = 0;
            }
        }
        
        if ($userCheckinDetail) {
            $checkImages->getDbTable()->setArrayObjectPrototype('ArrayObject');
            foreach ($userCheckinDetail as $key => $val) {
                $checkinImageOption = array('where' => array('checkin_id' => $val['id']));
                if ($checkImages->find($checkinImageOption)->toArray()) {
                    $checkinImagesDetail = $checkImages->find($checkinImageOption)->toArray();
                    $userCheckinDetail[$key]['images'] = $checkinImagesDetail;
                } else {
                    $userCheckinDetail[$key]['images'] = array();
                }
                if ($val['restaurant_image_name']) {
                    $userCheckinDetail[$key]['restaurant_image_name'] = 'munch_images/' . strtolower($val['rest_code']) . '/' . $val['restaurant_image_name'];
                } else {
                    $userCheckinDetail[$key]['restaurant_image_name'] = '';
                }
            }
            $totalCheckin = count($userCheckinDetail);
            if ($totalCheckin > 0) {
                $responses['checkin'] = array_slice($userCheckinDetail, $offset, $limit);
            } else {
                $responses['checkin'] = array();
            }
            
            $responses['total_checkin'] = $totalCheckin;            
        }else{
            $responses['checkin'] = array();
            $responses['total_checkin'] = 0; 
        }
        return $responses;
    }

    public function getFriendCheckIn($userId, $offset = false, $limit = false) {

        $userFriendModel = new UserFriends();
        $userFunctions = new UserFunctions();
        $friends = $userFriendModel->getUserFriendList($userId, 'date');

        $friendsIdArray = array();
        if (!empty($friends) && $friends != null) {
            foreach ($friends as $key => $value) {
                if ($userId != $value['friend_id']) {
                    $friendsIdArray[] = $value['friend_id'];
                }
            }
        }

        $friendCheckInData = array();
        if (!empty($friendsIdArray)) {
            $userCheckin = new UserCheckin();
            $checkImages = new CheckinImages();
            $userCheckin->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $joins = array();
            $joins [] = array(
                'name' => 'restaurants',
                'on' => 'restaurants.id = user_checkin.restaurant_id',
                'columns' => array(
                    'restaurant_name','address',
                    'latitude',
                    'longitude',
                    'restaurant_image_name',
                    'rest_code',
                    'is_restaurant_exists' => new \Zend\Db\Sql\Expression('if(inactive = 1 or closed = 1,"No","Yes")'),
                ),
                'type' => 'left'
            );
            $joins [] = array(
                'name' => 'users',
                'on' => 'users.id = user_checkin.user_id',
                'columns' => array(
                    'first_name',
                    'last_name',
                    'display_pic_url',
                ),
                'type' => 'left'
            );
            $options = array('where' => array('user_id' => $friendsIdArray), 'joins' => $joins, 'order' => array(
                    'user_checkin.id' => 'desc'
            ));
            if ($userCheckin->find($options)->toArray()) {
                $friendCheckInData = $userCheckin->find($options)->toArray();
            }

            if (!empty($friendCheckInData)) {
                $checkImages->getDbTable()->setArrayObjectPrototype('ArrayObject');
                foreach ($friendCheckInData as $key => $val) {
                    $checkinImageOption = array('where' => array('checkin_id' => $val['id'], 'status' => 1));
                    if ($val['restaurant_image_name']) {
                        $friendCheckInData[$key]['restaurant_image_name'] = 'munch_images/' . strtolower($val['rest_code']) . '/' . $val['restaurant_image_name'];
                    } else {
                        $friendCheckInData[$key]['restaurant_image_name'] = '';
                    }
                    $friendCheckInData[$key]['display_pic_url'] = $userFunctions->findImageUrlNormal($val['display_pic_url'], $val['user_id']);
                    if ($checkImages->find($checkinImageOption)->toArray()) {
                        $checkinImagesDetail = $checkImages->find($checkinImageOption)->toArray();
                        $friendCheckInData[$key]['images'] = $checkinImagesDetail;
                    } else {
                        $friendCheckInData[$key]['images'] = array();
                    }
                }
            }
            $friendCheckInData = array_slice($friendCheckInData, $offset, $limit);
        }
        
        return $friendCheckInData;
    }

    public function getTotalFriendCheckIn($userId) {

        $userFriendModel = new UserFriends();
        $userFunctions = new UserFunctions();
        $friends = $userFriendModel->getUserFriendList($userId, 'date');

        $friendsIdArray = array();
        if (!empty($friends) && $friends != null) {
            foreach ($friends as $key => $value) {
                if ($userId != $value['friend_id']) {
                    $friendsIdArray[] = $value['friend_id'];
                }
            }
        }
        $friendCheckInData = array();
        if (!empty($friendsIdArray)) {
            $userCheckin = new UserCheckin();
            $checkImages = new CheckinImages();
            $userCheckin->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $joins = array();
            $joins [] = array(
                'name' => 'restaurants',
                'on' => 'restaurants.id = user_checkin.restaurant_id',
                'columns' => array(
                    'restaurant_name','address',    
                    'latitude',
                    'longitude',
                    'restaurant_image_name',
                    'rest_code',
                    'is_restaurant_exists' => new \Zend\Db\Sql\Expression('if(inactive = 1 or closed = 1,"No","Yes")'),
                ),
                'type' => 'left'
            );
            $joins [] = array(
                'name' => 'users',
                'on' => 'users.id = user_checkin.user_id',
                'columns' => array(
                    'first_name',
                    'last_name',
                    'display_pic_url',
                ),
                'type' => 'left'
            );
            $options = array('where' => array('user_id' => $friendsIdArray), 'joins' => $joins, 'order' => array(
                    'user_checkin.id' => 'desc'
            ));
            if ($userCheckin->find($options)->toArray()) {
                $friendCheckInData = $userCheckin->find($options)->toArray();
            }
        }
        return $friendCheckInData;
    }

}
