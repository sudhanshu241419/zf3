<?php

namespace Bookmark\Controller;

use MCommons\Controller\AbstractRestfulController;
use Bookmark\Model\FoodBookmark;
use MCommons\StaticOptions;
use Restaurant\Model\Menu;
use User\UserFunctions;
use Restaurant\Model\Restaurant;

class FoodBookmarkController extends AbstractRestfulController {

    public function create($data) {
        echo "i m here";die;
        $bookmarkModel = new FoodBookmark ();
        $menuModel = new Menu ();
        $userNotificationModel = new \User\Model\UserNotification();
        $session = $this->getUserSession();
        $description = "";
        $bookmarkModel->user_id = $session->getUserId();
        $bookmarkModel->menu_id = isset($data ['menu_id']) ? $data ['menu_id'] : 0;
        $bookmarkModel->restaurant_id = $data ['restaurant_id'];
        $bookmarkModel->type = $data ['type'];
        $userFunctions = new UserFunctions();
        $locationData = $session->getUserDetail('selected_location', array());
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        if (!$bookmarkModel->user_id) {
            throw new \Exception("Invalid user", 400);
        }
        if (!$bookmarkModel->restaurant_id)
            throw new \Exception("Invalid restaurant id", 400);

        if (!$bookmarkModel->menu_id) {
            throw new \Exception("Invalid menu id", 400);
        }

        if (!$bookmarkModel->type) {
            throw new \Exception("Invalid bookmark type", 400);
        }

        $restaurantModel = new Restaurant();
        $restaurantDetailOption = array('columns' => array('rest_code', 'restaurant_name'), 'where' => array('id' => $data ['restaurant_id']));
        $restDetail = $restaurantModel->findRestaurant($restaurantDetailOption)->toArray();

        $menuModel = new Menu();
        $menuDetail = $menuModel->getMenuDetail($bookmarkModel->menu_id);

        $userModel = new \User\Model\User();
        $userDetailOption = array('columns' => array('first_name', 'last_name','email'), 'where' => array('id' => $session->getUserId()));
        $userDetail = $userModel->getUser($userDetailOption);
        $userName = (isset($userDetail['last_name']) && !empty($userDetail['last_name'])) ? $userDetail['first_name'] . " " . $userDetail['last_name'] : $userDetail['first_name'];
        $isMenuExists = $menuModel->isFoodExists($bookmarkModel->menu_id, $bookmarkModel->restaurant_id);
        $menuFind = $menuModel->getMenuDetail($bookmarkModel->menu_id);
        $identifier = '';
        $message = '';
        $item_name = (isset($menuFind['item_name']) && $menuFind['item_name'] != '') ? $menuFind['item_name'] : 'some food';
        if ($bookmarkModel->type === 'lo') {
            $identifier = 'loveFood';
            $message = "Love is priceless and never pointless. You earned one point for loving " . $item_name . " from " . $restDetail['restaurant_name'] . ".";
            $description = "loveit";
        } elseif ($bookmarkModel->type === 'wi') {
            $identifier = 'craveFood';
            $message = "You craved " . $item_name . " from " . $restDetail['restaurant_name'] . "! Here is 1 point to hold you over.";
            $description = "craveit";
        } elseif ($bookmarkModel->type === 'ti') {
            $identifier = 'tryFood';
            $message = "You have tried " . $item_name . " from " . $restDetail['restaurant_name'] . "! This calls for a celebration, here is 1 point!";
            $description = "tryit";
        }
        if (empty($identifier) || empty($message)) {
            throw new \Exception("Invalid type", 400);
        }
        $points = $userFunctions->getAllocatedPoints($identifier);



        $bookmarkModel->created_on = $currentDate;

        if ($isMenuExists) {
            $bookmarkModel->menu_name = $menuFind['item_name'];
        }

        $bookmarkModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array('columns' => array('menu_id', 'id'),
            'where' => array(
                'menu_id' => $bookmarkModel->menu_id,
                'user_id' => $bookmarkModel->user_id,
                'type' => $bookmarkModel->type
            )
        );
        $isAlreadyBookedmark = $bookmarkModel->find($options)->toArray();

        if (!empty($isAlreadyBookedmark)) {
            $response = array();
            $response['restaurant_id'] = $bookmarkModel->restaurant_id;
            $response['user_id'] = $bookmarkModel->user_id;
            $response['type'] = $bookmarkModel->type;
            $bookmarkModel->id = $isAlreadyBookedmark[0]['id'];
            $rowEffected = $bookmarkModel->delete();

            $userFunctions->takePoints($points, $bookmarkModel->user_id, $bookmarkModel->menu_id);
            $bookmarkCount = $bookmarkModel->getMenuBookmarkCountOfType($bookmarkModel->restaurant_id, $bookmarkModel->menu_id, $bookmarkModel->type);
            $totalBookMarkCountOfType = $bookmarkCount[0];
            if ($totalBookMarkCountOfType['total_count'] != 0) {
                if ($bookmarkModel->type == "ti") {
                    $response ['tried_count'] = $totalBookMarkCountOfType['total_count'];
                    $response ['user_tried_it'] = false;
                } elseif ($bookmarkModel->type == "lo") {
                    $response ['love_count'] = $totalBookMarkCountOfType['total_count'];
                    $response ['user_loved_it'] = false;
                } elseif ($bookmarkModel->type == "wi") {
                    $response ['crave_count'] = $totalBookMarkCountOfType['total_count'];
                    $response ['user_crave_it'] = false;
                }
            } else {
                if ($bookmarkModel->type == "ti") {
                    $response ['tried_count'] = "0";
                    $response ['user_tried_it'] = false;
                } elseif ($bookmarkModel->type == "lo") {
                    $response ['love_count'] = "0";
                    $response ['user_loved_it'] = false;
                } elseif ($bookmarkModel->type == "wi") {
                    $response ['crave_count'] = "0";
                    $response ['user_crave_it'] = false;
                }
            }
            
             $cleverTap = array(
                "user_id" => $bookmarkModel->user_id,
                "name" => $userName,
                "email" => $userDetail['email'],
                "identity"=>$userDetail['email'],
                "restaurant_name" => $restDetail['restaurant_name'],
                "restaurant_id" => $data['restaurant_id'],
                "eventname" => "uncheck_bookmark",
                "point_redeemed" => $points ['points'],
                "is_register" => "yes",
                "date" => $currentDate,
                "type" => "food",
                "description" =>$description,
                "menu_item"=>$item_name,
                "event"=>1,
                "bookmark_type"=>$bookmarkModel->type
                );
         $userFunctions->createQueue($cleverTap, 'clevertap');
        } else {
            $response = $bookmarkModel->addBookmark();
            $cleverTap = array(
                    "user_id" => $bookmarkModel->user_id,
                    "name" => $userName,
                    "email" => $userDetail['email'],
                    "identity"=>$userDetail['email'],
                    "restaurant_name" => $restDetail['restaurant_name'],
                    "restaurant_id" => $data['restaurant_id'],
                    "eventname" => "bookmark",
                    "earned_points" => $points ['points'],
                    "is_register" => "yes",
                    "date" => $currentDate,
                    "type" => "food",
                    "description" =>$description,
                    "menu item"=>$item_name,
                    "event"=>1,
                    "bookmark_type"=>$bookmarkModel->type
                );
            $userFunctions->createQueue($cleverTap, 'clevertap');
            #   Add activity feed data   # 
            if ($data ['type'] === 'lo') {
                $commonFunctiion = new \MCommons\CommonFunctions();
                $replacementData = array('food_item' => html_entity_decode($menuDetail['item_name']), 'restaurant_name' => $restDetail['restaurant_name']);
                $otherReplacementData = array('user_name' => $userDetail['first_name'], 'food_item' => html_entity_decode($menuDetail['item_name']), 'restaurant_name' => $restDetail['restaurant_name']);
                $menuInfo1 = array('id' => $menuDetail['id'], 'name' => html_entity_decode($menuDetail['item_name']));
                $menuInfo[] = $menuInfo1;
                $feed = array(
                    'restaurant_id' => $data['restaurant_id'],
                    'restaurant_name' => $restDetail['restaurant_name'],
                    'user_name' => ucfirst($userName),
                    'restaurant_code' => strtolower($restDetail['rest_code']),
                    'menu_image' => $menuDetail['image_name'],
                    'menu_id' => $menuDetail['id'],
                    'menu_name' => html_entity_decode($menuDetail['item_name']),
                    'menuinfo' => $menuInfo,
                    'img' => array()
                );
                $activityFeed = $commonFunctiion->addActivityFeed($feed, 12, $replacementData, $otherReplacementData);
                $notificationMsg = "That's so sweet! Or Salty! Either way, we hope you and " . $restDetail['restaurant_name'] . " make it work!";
                $channel = "mymunchado_" . $session->getUserId();
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $session->getUserId(),
                    "type" => 'bookmark',
                    "restaurantId" => $data['restaurant_id'],
                    'curDate' => $currentDate,
                    'restaurant_name' => ucfirst($restDetail['restaurant_name']),
                    'first_name' => ucfirst($userDetail['first_name']),
                    'user_id' => $session->getUserId(),
                    'is_food' => 1,
                    'btype' => 0,
                    'menu_id' => $menuDetail['id'],
                    'menu_name' => html_entity_decode($menuDetail['item_name'])
                );
                $notificationJsonArray = array('menu_id' => $menuDetail['id'], 'menu_name' => html_entity_decode($menuDetail['item_name']), 'btype' => 0, 'first_name' => ucfirst($userDetail['first_name']), 'user_id' => $session->getUserId(), 'is_food' => 1, 'restaurant_id' => $data['restaurant_id'], 'restaurant_name' => ucfirst($restDetail['restaurant_name']));
                // $pub = $userNotificationModel->createPubNubNotification($notificationArray,$notificationJsonArray);        
                //$pubnub = StaticOptions::pubnubPushNotification($notificationArray);
            } elseif ($data ['type'] === 'wi') {
                $commonFunctiion = new \MCommons\CommonFunctions();
                $replacementData = array('menu_name' => html_entity_decode($menuDetail['item_name']), 'restaurant_name' => $restDetail['restaurant_name']);
                $otherReplacementData = array('user_name' => ucfirst($userDetail['first_name']), 'menu_name' => html_entity_decode($menuDetail['item_name']), 'restaurant_name' => $restDetail['restaurant_name']);
                $feed = array(
                    'restaurant_id' => $data['restaurant_id'],
                    'restaurant_name' => $restDetail['restaurant_name'],
                    'restaurant_code' => strtolower($restDetail['rest_code']),
                    'menu_image' => $menuDetail['image_name'],
                    'menu_id' => $menuDetail['id'],
                    'menu_name' => html_entity_decode($menuDetail['item_name']),
                    'user_name' => ucfirst($userName),
                    'img' => array()
                );
                $activityFeed = $commonFunctiion->addActivityFeed($feed, 51, $replacementData, $otherReplacementData);
                $restaurantFunctions = new \Restaurant\RestaurantDetailsFunctions();
                $restuarantAddress = $restaurantFunctions->restaurantAddress($data['restaurant_id']);
                $salesData['owner_email'] = 'no-reply@munchado.com';
                $salesData['email'] = $userDetail['email'];    
                $salesData['restaurant_name'] = $restDetail['restaurant_name']; 
                $salesData['restaurant_id'] = $data['restaurant_id']; 
                $salesData['value']=$points ['points'];
                $salesData['description'] = "craved_food_menu";
                $salesData['contact_ext_event_type'] = "OTHER"; 
                $salesData['location'] = $restuarantAddress;
                $salesData['identifier']="event";              
                
                //$userFunctions->createQueue($salesData,'Salesmanago');
//               $notificationMsg='You know you want '.html_entity_decode($menuDetail['item_name']).' from '.$restDetail['restaurant_name'].'.';
//               $channel = "mymunchado_" . $session->getUserId();
//               $notificationArray = array(
//                    "msg" => $notificationMsg,
//                    "channel" => $channel,
//                    "userId" => $session->getUserId(),
//                    "type" => 'bookmark',    
//                    "restaurantId" => $data['restaurant_id'],        
//                    'curDate' => $currentDate,
//                    'restaurant_name'=>ucfirst($restDetail['restaurant_name']),
//                    'menu_id'=>$menuDetail['id'],
//                    'menu_name'=>html_entity_decode($menuDetail['item_name']),
//                    'first_name'=>ucfirst($userDetail['first_name']),
//                   'user_id'=>$session->getUserId(),
//                   'is_food'=>1,'btype'=>1
//                );
//                $notificationJsonArray = array('btype'=>1,'first_name'=>ucfirst($userDetail['first_name']),'user_id'=>$session->getUserId(),'is_food'=>1,'menu_id'=>$menuDetail['id'],'menu_name'=>html_entity_decode($menuDetail['item_name']),'restaurant_id' => $data['restaurant_id'],'restaurant_name'=>ucfirst($restDetail['restaurant_name']));
//                $pub = $userNotificationModel->createPubNubNotification($notificationArray,$notificationJsonArray);        
//                $pubnub = StaticOptions::pubnubPushNotification($notificationArray);
            } elseif ($data ['type'] === 'ti') {
                $commonFunctiion = new \MCommons\CommonFunctions();
                $replacementData = array('menu_name' => html_entity_decode($menuDetail['item_name']), 'restaurant_name' => $restDetail['restaurant_name']);
                $otherReplacementData = array('user_name' => ucfirst($userDetail['first_name']), 'menu_name' => html_entity_decode($menuDetail['item_name']), 'restaurant_name' => $restDetail['restaurant_name']);
                $feed = array(
                    'restaurant_id' => $data['restaurant_id'],
                    'restaurant_name' => $restDetail['restaurant_name'],
                    'restaurant_code' => strtolower($restDetail['rest_code']),
                    'menu_image' => $menuDetail['image_name'],
                    'menu_id' => $menuDetail['id'],
                    'menu_name' => html_entity_decode($menuDetail['item_name']),
                    'user_name' => ucfirst($userName),
                    'img' => array()
                );
                $activityFeed = $commonFunctiion->addActivityFeed($feed, 56, $replacementData, $otherReplacementData);
            }

            ###############################
            if ($response) {
                $userFunctions->givePoints($points, $bookmarkModel->user_id, $message, $bookmarkModel->menu_id);
                $bookmarkCount = $bookmarkModel->getMenuBookmarkCount($bookmarkModel->restaurant_id, $bookmarkModel->menu_id);

                foreach ($bookmarkCount as $key => $val) {
                    if ($val ['type'] == 'lo' && $bookmarkModel->type == 'lo') {
                        $response ['love_count'] = isset($val ['total_count']) ? $val ['total_count'] : (int) 0;
                        $response ['user_loved_it'] = true;
                        return $response;
                    }
                    if ($val ['type'] == 'ti' && $bookmarkModel->type == 'ti') {
                        $response ['tried_count'] = isset($val ['total_count']) ? $val ['total_count'] : (int) 0;
                        $response ['user_tried_it'] = true;
                        return $response;
                    }
                    if ($val ['type'] == 'wi' && $bookmarkModel->type == 'wi') {
                        $response ['crave_count'] = isset($val ['total_count']) ? $val ['total_count'] : (int) 0;
                        $response ['user_crave_it'] = true;
                        return $response;
                    }
                }

                unset($response ['type']);
                unset($response ['id']);
                unset($response ['user_id']);
            } else {
                throw new \Exception("Unable to save restaurant bookmark", 400);
            }
        }
        return $response;
    }

}
