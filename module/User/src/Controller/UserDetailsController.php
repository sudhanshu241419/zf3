<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Functions\UserFunctions;
use User\Model\User;
use User\Model\UserPoints;
use User\Model\UserPoint;
use Zend\Db\Sql\Expression;
use MCommons\StaticFunctions;

class UserDetailsController extends AbstractRestfulController {

    public function create($data) { 
        $userFunctions=$this->getServiceLocator(UserFunctions::class);
        $data['source'] = StaticFunctions::$_userAgent;        
        return $userFunctions->registerUser($data);
    }

    public function getList() {
        
        $session = $this->getUserSession();
        $locationData = $session->getUserDetail('selected_location');    
        if (!$session->isLoggedIn()) {
            throw new \Exception('No Active Login Found',400);
        }
        $friendId = $this->getQueryParams('friendid',false);
        if($friendId){
            $userId = $friendId;
        }else{
            $userId = $session->getUserId();
        }
        $userModel = new User ();       
        $userPointsModel = new UserPoints();
        $userPoint = new UserPoint();
        $userFunctions = new UserFunctions();
        $UserRestaurantImageModel = new \User\Model\UserRestaurantimage();
        $version = $this->getQueryParams('version', false);
        $userLastActivity = $userFunctions->getUserLastActivity($userId,$version);
        $options = array(
            'columns' => array(
                'first_name',
                'last_name',
                'join_date' => 'created_at',
                'profile_pic' => 'display_pic_url',
                'profile_pic_n'=>'display_pic_url_normal',
                'profile_pic_l'=>'display_pic_url_large',
                'points',
                'wallpaper',
                'email',
                'phone'
            ),
            'where' => array(
                'users.id' => $userId
            ),
        );

        $userModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        if($userModel->find($options)->toArray()){
            $userDetails = $userModel->getUserDetailWithStatisticsDetails($userId);
            $optRestaurantImages= array(
            'columns' => array('total_images' => new Expression("count(id)")),
            'where' => array('user_id' => $userId)
            );
            $UserRestaurantImageModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $myRestaurantImages = $UserRestaurantImageModel->find($optRestaurantImages)->toArray();
            $myRestaurantTotalImage=$userModel->userTotalImages($userId);
            $response = $userModel->find($options)->toArray()[0];
            $totalpoints = $userPoint->countUserPoints($userId);
            $redeem_points = $totalpoints[0]['redeemed_points'];
            $userGetPoints= strval($totalpoints[0]['points'] - $redeem_points);
            $response['id'] = $userId;
            $response['profile_pic'] = $userFunctions->findImageUrlNormal($response['profile_pic_l'], $userId);
            //$response['profile_pic_n'] = $userFunctions->findImageUrlNormal($response['profile_pic_n'], $userId);
            //$response['profile_pic_l'] = $userFunctions->findImageUrlNormal($response['profile_pic_l'], $userId);
            $response['points']=$userGetPoints;
            $response['order_count'] = $userDetails['order_count'];
            $response['groups_order_count']=$userDetails['groups_order_count'];
            $response['reservation_count'] = $userDetails['reservation_count']+$userDetails['reservation_with_count'];
            $response['reservation_count']=(string) $response['reservation_count'];
            $response['groups_reservation_count'] = $userDetails['reservation_with_count'];
            $response['friends_count'] = $userDetails['friends_count'];
            $response['reviews_count'] = $userDetails['reviews_count'];
            $response['total_photos'] = $myRestaurantImages[0]['total_images']+$myRestaurantTotalImage;
            $response['bookmarks_count'] = $userDetails['bookmarks_count'];
            $response['deals_count']=$userDetails['deals_count'];
            $response['snag_a_spot_count'] = $userDetails['snag_a_spot_count'];
    // below data is dummy and needs to be replaced with actual data before release
            $response['checkins'] = $userDetails['total_checkin'];
            $response['background_img'] = (isset($response['wallpaper']) && $response['wallpaper']!="")?WEB_URL.USER_IMAGE_WALLPAPER . DS . $userId . DS.$response['wallpaper']:Null;
            $mymuncher = $userFunctions->getMyLastEarnedMuncher();
            if($mymuncher){
            $response['munchers'] = $mymuncher['total_earned'];
            unset($mymuncher['total_earned'],$response['wallpaper']);
            $response['last_muncher_earned'] = $mymuncher;
            }else{
                $response['munchers'] = 0;
                $response['last_muncher_earned'] = null;
            }
            $response['last_activity_info'] = $userLastActivity ? $userLastActivity[0] : Null;
            
            $response['city']=$locationData['city_name'];
            ########### Promocode Detail ###############
            $promocode = $this->getServiceLocator()->get("Restaurant\Controller\UserPromocodesController");
            $promocodeDetails = $promocode->getList();            
            if(!empty($promocodeDetails)){                
                $response['coupon']=$promocodeDetails[0];
            }
        }else{
             throw new \Exception('Not valid detail',400);
        }

        return $response;
    }
    public function get($userId) {
        $friendDetail = array();
        $userModel = new User();
        $userFunctionModel = new UserFunctions();
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        if ($isLoggedIn) {
            $userId1 = $session->getUserId();
        } else {
            throw new \Exception('User detail not found', 400);
        }
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $userFunctionModel->userCityTimeZone($locationData);
        $friendDetail = $userModel->getUserDetailWithStatisticsDetails($userId, $userId1);
        if ($friendDetail) {
            if ($friendDetail['last_login']) {
                $friendDetail['last_login'] = $userFunctionModel->timeLater($currentDate, $friendDetail['last_login'], 'ago');
            }
            $friendDetail = array_map(function ($i) {
                return $i === null ? '' : $i;
            }, $friendDetail);
        }

        $friendDetail['display_pic_url'] = $userFunctionModel->findImageUrlNormal($friendDetail['display_pic_url'], $userId);
        $friendDetail = array_map(function ($i) {
            return $i === null ? '' : $i;
        }, $friendDetail);
        return $friendDetail;
    }
}
