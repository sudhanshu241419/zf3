<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\UserFriends;
use User\Model\User;
use City\Model\City;
use User\Model\UserReferrals;

class UserFriendController extends AbstractRestfulController {

    const FORCE_LOGIN = true;

    public function getList() {
        $data['referral_info'] = $data['invitation'] = $data['pending_invitation'] =  $data['user_dine_restaurant'] = [];
        $data['referral_code'] = '';
        $userFriendModel = $this->getServiceLocator(UserFriends::class);
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $userFriendInvitations = $this->getServiceLocator(UserFriendsInvitation::class);
        $cityModel = $this->getServiceLocator(City::class);
        $session = $this->getUserSession();
        $friendId = $this->getQueryParams('friendid', false);
        $isLoggedIn = $session->isLoggedIn();
        
        if ($isLoggedIn) {
            if ($friendId) {
                $userId = $friendId;
            } else {
                $userId = $session->getUserId();
                $userModel = $this->getServiceLocator(User::class);
                $userEmailData = $userModel->getUserEmail($userId);
                $userEmail=$userEmailData['email'];
            }
        } else {
            throw new \Exception('User detail not found', 404);
        }
        if (!$friendId) {
            $data['my_friends'] = [];
        } else {
            $data['friends_friend'] = [];
        }
        $orderby = $this->getQueryParams('orderby', 'date');
        $page = $this->getQueryParams('page', 1);
        $limit = $this->getQueryParams('limit', SHOW_PER_PAGE);
        $offset = 0;
        if ($page > 0) {
            $page = ($page < 1) ? 1 : $page;
            $offset = ($page - 1) * ($limit);
        }
        
        $friends = $userFriendModel->getUserFriendList($userId, $orderby);
        $userReferralmodel = $this->getServiceLocator(UserReferrals::class);
        $referredUsersList = $userReferralmodel->getReferredUsersArr($userId);
        $earningAndCycles = $this->getUserReferralEarningAndCycles($userId);
        if (!empty($friends) && $friends != null) {
            $i = 0;
            if (!$friendId) {
                $totalFriends = count($friends);
                $data['total_friends'] = $totalFriends;
            }
            foreach ($friends as $key => $value) {                      
                if ($friendId) {
                    $value['display_pic_url'] = $userFunctions->findImageUrlNormal($value['display_pic_url'], $value['friend_id']);
                    $data['friends_friend'][$i] = $value;
                    if ($value['city_id'] != null && !empty($value['city_id'])) {
                        $cityDetails = $cityModel->fetchCityDetails($value['city_id']);
                        $data['friends_friend'][$i]['city'] = $cityDetails['city_name'];
                    } else {
                        $data['friends_friend'][$i]['city'] = NULL;
                    }
                    $data['referral_code'] = $this->getUserReferralCode($value['friend_id']);
                    $data['order_placed'] = (int)$this->hasPlacedOrder($value['friend_id']);
                    $data['is_referred'] = intval(in_array(intval($value['friend_id']), $referredUsersList));
                } elseif (!$friendId) {
                    $value['display_pic_url'] = $userFunctions->findImageUrlNormal($value['display_pic_url'], $value['friend_id']);
                    $data['my_friends'][$i] = $value;
                    if ($value['city_id'] != null && !empty($value['city_id'])) {
                        $cityDetails = $cityModel->fetchCityDetails($value['city_id']);
                        $data['my_friends'][$i]['city'] = $cityDetails['city_name'];
                    } else {
                        $data['my_friends'][$i]['city'] = NULL;
                    }
                    $data['my_friends'][$i]['referral_code'] = $this->getUserReferralCode($value['friend_id']);
                    $data['my_friends'][$i]['order_placed'] = (int)$this->hasPlacedOrder($value['friend_id']);
                    $data['my_friends'][$i]['is_referred'] = intval(in_array(intval($value['friend_id']), $referredUsersList));
                }
                $i++;
            }
        }
        if (!$friendId) {
            $comingInvitation = $userFriendInvitations->getUserInvitationList($userEmail, $orderby);
            if (!empty($comingInvitation) && $comingInvitation != null) {
                $i = 0;
                foreach ($comingInvitation as $k => $val) {
                    $val['display_pic_url'] = $userFunctions->findImageUrlNormal($val['display_pic_url'], $val['user_id']);
                    $data['invitation'][$i] = $val;
                    if ($val['city_id'] != null && !empty($val['city_id'])) {
                        $cityDetails = $cityModel->fetchCityDetails($val['city_id']);
                        $data['invitation'][$i]['city'] = $cityDetails['city_name'];
                    } else {
                        $data['invitation'][$i]['city'] = NULL;
                    }
                    $i++;
                }
            }
            $pendingInvitation = $userFriendInvitations->getComingInvitationList($userId, $orderby);
            if (!empty($pendingInvitation) && $pendingInvitation != null) {
                $i = 0;
                foreach ($pendingInvitation as $ky => $val1) {
                    $val1['display_pic_url'] = $userFunctions->findImageUrlNormal($val1['display_pic_url'], $val1['user_id']);
                    $data['pending_invitation'][$i] = $val1;
                    if ($val1['city_id'] != null && !empty($val1['city_id'])) {
                        $cityDetails = $cityModel->fetchCityDetails($val1['city_id']);
                        $data['pending_invitation'][$i]['city'] = $cityDetails['city_name'];
                    } else {
                        $data['pending_invitation'][$i]['city'] = NULL;
                    }
                    $i++;
                }
            }
        }
        if (!empty($data['my_friends'])) {
            $myfriends = array_slice($data['my_friends'], $offset, $limit);
            $data['my_friends'] = $myfriends;
        }
        if (!empty($userId)) {           
            $data['referral_info']['referral_left'] = $this->getUserReferralOrderRemainingCount($userId);
            $data['referral_info']['referral_earning'] = $earningAndCycles['earning'];
            $data['referral_info']['referral_cycles'] = $earningAndCycles['cycles'] + 1;
            $restaurantServer = $this->getServiceLocator(\Restaurant\Model\RestaurantServer::class);
            $userDineAndMoreRestaurant = $restaurantServer->userDineAndMoreRestaurant($userId);
            $commonFunctions = $this->getServiceLocator(\MCommons\CommonFunctions::class);
            $commonFunctions->replaceParticulerKeyValueInArray($userDineAndMoreRestaurant);
            
            $dineAmdMoreMunchado = array(
                "code" => MUNCHADO_DINE_MORE_CODE,
                "restaurant_id" => "",
                "restaurant_name" => "Munch Ado",
                "restaurant_image_name" =>"",
                "rest_code" => "",
                "tag_id" => "",
                "rest_short_url" =>"" );
            array_push($userDineAndMoreRestaurant,$dineAmdMoreMunchado);          
            $data['user_dine_restaurant'] = $userDineAndMoreRestaurant;
            $data['referral_code'] = $this->getUserReferralCode($userId);
        }  
        
        return $data;
    }

    private function getUserReferralOrderRemainingCount($user_id) {
        $ur = new UserReferrals();
        $placed_count = intval($ur->getUserReferralOrderPlacedCount($user_id)) % 3;
        $remaining = ($placed_count > 2) ? 0 : (3 - $placed_count);
        return strval($remaining);
    }

    /**
     * Returns user earning and cycles considering $30 credit=1 cycle
     * @param int $userId
     * @return array with keys earning and cycles
     */
    private function getUserReferralEarningAndCycles($userId) {
        $userTransactionModel = $this->getServiceLocator(\User\Model\UserTransactions::class);
        $earning = floatval($userTransactionModel->getUserReferralEarning($userId));
        $userReferralModel = $this->getServiceLocator(UserReferrals::class);
        $count = $userReferralModel->getTotalReferredUsersWithAmountCredited($userId);
        return ['earning' => $earning, 'cycles' => (intval($count / 3))];
    }

    private function getUserReferralCode($userId) {
        $user_model = $this->getServiceLocator(User::class);
        $isMob = $this->isMobile();        
        return $user_model->getUserReferralCode($userId,$isMob);
    }

    private function hasPlacedOrder($userId) {
        $userOrderModel = $this->getServiceLocator(UserOrder::class);
        $count = $userOrderModel->getTotalPlacedOrder($userId);
        if ($count > 0) {
            return 1;
        }
        return 0;
    }

}
