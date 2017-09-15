<?php

namespace Bookmark\Controller;

use MCommons\Controller\AbstractRestfulController;
use Restaurant\Model\RestaurantBookmark;
use MCommons\StaticFunctions;
use Restaurant\Model\Restaurant;
use User\Functions\UserFunctions;
use User\Model\User;
use MCommons\CommonFunctions;

class RestaurantBookmarkController extends AbstractRestfulController {

    public function create($data) {
        $bookmarkModel = $this->getServiceLocator(RestaurantBookmark::class);
        $restModel = $this->getServiceLocator(Restaurant::class);
        $session = $this->getUserSession();
        $description = '';
        $bookmarkModel->user_id = $session->getUserId();
        $data = StaticFunctions::filterRequestParams($data);
        $bookmarkModel->restaurant_id = $data ['restaurant_id'];
        $bookmarkModel->type = $data ['type'];
        $isRestExists = $restModel->isRestaurantExists($bookmarkModel->restaurant_id);
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $locationData = $session->getUserDetail('selected_location', []);
        $currentDate = $userFunctions->userCityTimeZone($locationData);
        if (!$isRestExists) {
            throw new \Exception("Invalid restaurant", 400);
        }
        if (!$bookmarkModel->user_id) {
            throw new \Exception("Invalid user", 400);
        }
        if (empty($bookmarkModel->restaurant_id) || empty($bookmarkModel->type)) {
            throw new \Exception("Invalid restaurant detail", 400);
        }

        if (!$bookmarkModel->type) {
            throw new \Exception("Invalid bookmark type", 400);
        }
        if ($isRestExists) {
            $restaurant = $restModel->findByRestaurantId([
                'column' => ['restaurant_name', 'rest_code', 'restaurant_image_name'],
                'where' => ['id' => $bookmarkModel->restaurant_id]
            ]);
            $bookmarkModel->restaurant_name = isset($restaurant['restaurant_name']) ? $restaurant['restaurant_name'] : "";
        }

        $userModel = $this->getServiceLocator(User::class);
        $userDetailOption = ['columns' => ['first_name', 'last_name', 'email'], 'where' => ['id' => $session->getUserId()]];
        $userDetail = $userModel->getUser($userDetailOption);
        $userName = (isset($userDetail['last_name']) && !empty($userDetail['last_name'])) ? $userDetail['first_name'] . " " . $userDetail['last_name'] : $userDetail['first_name'];
        $bookmarkModel->created_on = $currentDate;
        // check for existing record
        $isAlreadyBookedmark = $bookmarkModel->isAlreadyBookmark([
            'type' => $bookmarkModel->type,
            'restaurant_id' => $bookmarkModel->restaurant_id,
            'user_id' => $bookmarkModel->user_id
        ]);
        if ($bookmarkModel->type === 'lo') {
            $identifier = 'loveRestaurant';
            $message = "Your love is what makes the world go round. We value it at 1 point per restaurant.";
            $description = "loveit";
        } elseif ($bookmarkModel->type === 'wl') {
            $identifier = 'craveitrestaurant';
            $message = "You’re craving " . $bookmarkModel->restaurant_name . " something fierce. That’s worth a point.";
            $description = "craveit";
        } elseif ($bookmarkModel->type === 'bt') {
            $identifier = 'beenthererestaurant';
            $message = "You told us you’ve been to " . $bookmarkModel->restaurant_name . ". Your tale of food conquest has earned you 1 point.";
            $description = "beenthere";
        }
        $points = $userFunctions->getAllocatedPoints($identifier);

        if (!empty($isAlreadyBookedmark)) {
            $response = array();
            $response['restaurant_id'] = $bookmarkModel->restaurant_id;
            $response['user_id'] = $bookmarkModel->user_id;
            $response['type'] = $bookmarkModel->type;
            $bookmarkModel->id = $isAlreadyBookedmark[0]['id'];
            $rowEffected = $bookmarkModel->delete();
            $userFunctions->takePoints($points, $bookmarkModel->user_id, $bookmarkModel->restaurant_id);
            $bookmarkCount = $bookmarkModel->getRestaurantBookmarkCountOfType($bookmarkModel->restaurant_id, $bookmarkModel->type);
            $resBookmarkCount = $bookmarkCount[0];
            if ($resBookmarkCount['total_count'] != 0) {
                if ($bookmarkModel->type == 'lo') {
                    $response ['love_count'] = $resBookmarkCount['total_count'];
                    $response ['user_loved_it'] = false;
                } elseif ($bookmarkModel->type == 'bt') {
                    $response ['been_count'] = $resBookmarkCount['total_count'];
                    $response ['user_been_there'] = false;
                } elseif ($bookmarkModel->type == 'wl') {
                    $response ['crave_count'] = $resBookmarkCount['total_count'];
                    $response ['user_crave_it'] = false;
                }
            } else {
                if ($bookmarkModel->type == "bt") {
                    $response ['been_count'] = "0";
                    $response ['user_been_there'] = false;
                } elseif ($bookmarkModel->type == "lo") {
                    $response ['love_count'] = "0";
                    $response ['user_loved_it'] = false;
                } elseif ($bookmarkModel->type == "wl") {
                    $response ['crave_count'] = "0";
                    $response ['user_crave_it'] = false;
                }
            }
            $cleverTap = array(
                "user_id" => $bookmarkModel->user_id,
                "name" => $userName,
                "email" => $userDetail['email'],
                "identity" => $userDetail['email'],
                "restaurant_name" => $bookmarkModel->restaurant_name,
                "restaurant_id" => $data ['restaurant_id'],
                "eventname" => "uncheck_bookmark",
                "point_redeemed" => $points ['points'],
                "is_register" => "yes",
                "date" => $currentDate,
                "type" => "restaurant",
                "description" => $description,
                "event" => 1,
                "bookmark_type" => $bookmarkModel->type
            );
            $userFunctions->createQueue($cleverTap, 'clevertap');
        } else {
            $response = $bookmarkModel->addRestaurantBookMark();
            $cleverTap = array(
                "user_id" => $bookmarkModel->user_id,
                "name" => $userName,
                "email" => $userDetail['email'],
                "identity" => $userDetail['email'],
                "restaurant_name" => $bookmarkModel->restaurant_name,
                "restaurant_id" => $data ['restaurant_id'],
                "eventname" => "bookmark",
                "earned_points" => $points ['points'],
                "is_register" => "yes",
                "date" => $currentDate,
                "type" => "restaurant",
                "description" => $description,
                "event" => 1,
                "bookmark_type" => $bookmarkModel->type
            );
            $userFunctions->createQueue($cleverTap, 'clevertap');
            $commonFunctiion = $this->getServiceLocator(CommonFunctions::class);
            if ($data ['type'] === 'lo') {
                $replacementData = array('restaurant_name' => $restaurant['restaurant_name']);
                $otherReplacementData = array('user_name' => ucfirst($userName), 'restaurant_name' => $restaurant['restaurant_name']);
                $feed = array(
                    'restaurant_id' => $data ['restaurant_id'],
                    'restaurant_name' => $restaurant['restaurant_name'],
                    'restaurant_image' => $restaurant['restaurant_image_name'],
                    'restaurant_code' => strtolower($restaurant['rest_code']),
                    'user_name' => ucfirst($userName),
                    'img' => array()
                );
                $activityFeed = $commonFunctiion->addActivityFeed($feed, 16, $replacementData, $otherReplacementData);
                $notificationMsg = 'Aww, its so nice to see true love between you and ' . $restaurant['restaurant_name'] . '.';
                $channel = "mymunchado_" . $session->getUserId();
                $notificationArray = array(
                    "msg" => $notificationMsg,
                    "channel" => $channel,
                    "userId" => $session->getUserId(),
                    "type" => 'bookmark',
                    "restaurantId" => $data['restaurant_id'],
                    'curDate' => $currentDate,
                    'restaurant_name' => ucfirst($restaurant['restaurant_name']),
                    'is_food' => 0,
                    'first_name' => ucfirst($userDetail['first_name']),
                    'user_id' => $session->getUserId(),
                    'btype' => 0
                );
                $notificationJsonArray = array('btype' => 0, 'is_food' => 0, 'first_name' => ucfirst($userDetail['first_name']), 'user_id' => $session->getUserId(), 'restaurant_id' => $data['restaurant_id'], 'restaurant_name' => ucfirst($restaurant['restaurant_name']));
            } elseif ($data ['type'] === 'wl') {
                $replacementData = array('restaurant_name' => $restaurant['restaurant_name']);
                $otherReplacementData = array('user_name' => ucfirst($userName), 'restaurant_name' => $restaurant['restaurant_name']);
                $feed = array(
                    'restaurant_id' => $data ['restaurant_id'],
                    'restaurant_name' => $restaurant['restaurant_name'],
                    'restaurant_image' => $restaurant['restaurant_image_name'],
                    'restaurant_code' => strtolower($restaurant['rest_code']),
                    'user_name' => ucfirst($userName),
                    'img' => array()
                );
                $activityFeed = $commonFunctiion->addActivityFeed($feed, 17, $replacementData, $otherReplacementData);
            } elseif ($data ['type'] === 'bt') {
                $replacementData = array('restaurant_name' => $restaurant['restaurant_name']);
                $otherReplacementData = array('user_name' => ucfirst($userName), 'restaurant_name' => $restaurant['restaurant_name']);
                $feed = array(
                    'restaurant_id' => $data ['restaurant_id'],
                    'restaurant_name' => $restaurant['restaurant_name'],
                    'restaurant_image' => $restaurant['restaurant_image_name'],
                    'restaurant_code' => strtolower($restaurant['rest_code']),
                    'user_name' => ucfirst($userName),
                    'img' => array()
                );
                $activityFeed = $commonFunctiion->addActivityFeed($feed, 55, $replacementData, $otherReplacementData);
            }
            if ($response) {
                $userFunctions->givePoints($points, $bookmarkModel->user_id, $message, $bookmarkModel->restaurant_id);
                $bookmarkCount = $bookmarkModel->getRestaurantBookmarkCount($bookmarkModel->restaurant_id);
                foreach ($bookmarkCount as $key => $val) {
                    if ($val ['type'] == 'lo' && $bookmarkModel->type == 'lo') {
                        $response ['love_count'] = isset($val ['total_count']) ? $val ['total_count'] : "0";
                        $response ['user_loved_it'] = true;
                        return $response;
                    }
                    if ($val ['type'] == 'bt' && $bookmarkModel->type == 'bt') {
                        $response ['been_count'] = isset($val ['total_count']) ? $val ['total_count'] : "0";
                        $response ['user_been_there'] = true;
                        return $response;
                    }
                    if ($val ['type'] == 'wl' && $bookmarkModel->type == 'wl') {
                        $response ['crave_count'] = isset($val ['total_count']) ? $val ['total_count'] : "0";
                        $response ['user_crave_it'] = true;
                        return $response;
                    }
                }
                unset($response ['type']);
                unset($response ['id']);
                unset($response ['user_id']);
                unset($response ['restaurant_id']);
            } else {
                throw new \Exception("Unable to save restaurant bookmark", 400);
            }
        }
        return $response;
    }
    public function setVariableAndCheckValidationAndSession($data) {
        
    }
    public function returnBookmarkType() {
        $bookmark = '';
        return $bookmark;
    }
}
