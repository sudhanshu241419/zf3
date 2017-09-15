<?php

namespace User\Functions;

use MCommons\StaticFunctions;
use User\Model\UserInvitation;
use User\Model\UserReservation;
use User\Model\User;
use User\Model\UserOrder;
use Restaurant\Model\PreOrderItem;
use Restaurant\Model\PreOrder;
use User\Model\DbTable\UserTable as userdbtable;
use User\Form\LoginForm;
//use User\FormFilter\LoginFormFilter;
use User\Model\PointSourceDetails;
use User\Model\UserCard;
use MStripe;
use User\Model\UserPoint;
use Restaurant\Model\RestaurantAccounts;
use User\Model\UserOrderDetail;
use User\Model\UserFriendsInvitation;
use User\Model\UserFriends;
use User\Model\UserSetting;
use User\Model\UserAddress;
use Restaurant\Model\Restaurant;
use User\Model\UserNotification;
use Restaurant\OrderFunctions;
use MCommons\CommonFunctions;
use Zend\Db\Sql\Predicate\Expression;
use User\Model\UserAccount;
use User\Model\UserReferrals;
use User\Model\UserPromoCodes;
use Restaurant\Model\RestaurantServer;
use Restaurant\Model\Tags;
use Auth\Model\Auth;
use Restaurant\Model\RestaurantDealsCoupons;
use User\Model\Avatar;
use User\Model\UserAvatar;

class UserFunctions {

    public $currentDateTimeUnixTimeStamp;
    public $promocodeId;
    public $userId;
    public $userPromocodes = [];
    public $email;
    public $loyaltyReg;
    public $loyaltyCode = false;
    public $restaurantId;
    public $restaurant_name = '';
    public $isRegisterUser = false;
    public $vendorNumber;
    public $smsRegistrationPassword = false;
    public $first_name = false;
    public $existReg = false;
    public $isRegisterWithRestaurant = 0;
    public $resDealId = [];
    public $userDeals = [];
    public $totalUnreadDeals = 0;
    public $referral = false;
    public $referralJoin = false;
    public $friendId = false;
    public $total_order = 0;
    public $order_amount = 0;
    public $activityDate = false;
    public $imageUploadCount = 0;
    public $orderId;
    public $orderType;
    public $typeKey;
    public $typeValue;
    public $rest_code = '';
    public $inviter_name = false;
    public $requestByServerForNewUserRegistration = 0;
    public $totalRegisterServer;
    public $cityId;
    public $redeemPoint = 0;
    public $offer = [];
    public $offerId = [];
    public $refferUserDetails;
    public $referralPoint = 0;
    public $host_name;
    public $restaurant_logo;
    public $restaurant_address;
    public $facebook_url;
    public $twitter_url;
    public $instagram_url;

    public function ReplaceNullInArray($data) {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                foreach ($value as $k => $v) {
                    if (!isset($v)) {
                        $data [$key] [$k] = "";
                    }
                }
            }
        }
        return $data;
    }

    public function userCityTimeZone($locationData) {
        $stateCode = isset($locationData ['state_code']) ? $locationData ['state_code'] : 'NY';
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $stateCode
        ));
        return $cityDateTime->format("Y-m-d H:i:s");
    }

    public function getReservationInvitationAdmitted($options = array(), $flag = null) {
        $allInvitationAdmitted = array();
        $reservation = array();
        $userReservationInviteModel = new UserInvitation ();
        $userReservationModel = new UserReservation ();
        $allInvitationAdmitted = $userReservationInviteModel->getInvitationAdmitted($options ['userId'], $options ['msg_status']);

        if (!empty($allInvitationAdmitted)) {
            foreach ($allInvitationAdmitted as $value) {
                $id [] = $value ['reservation_id'];
            }
            $reservation = $userReservationModel->getReservationMultiId($options ['userId'], $id, $options ['status'], $options ['currentDate'], $flag);
        }

        return $reservation;
    }

    /*
     * Find the Meal Slot
     */

    public function getMealSlot($timeSlot) {
        if ($timeSlot >= '05:00:00' & $timeSlot <= '12:00:00')
            return 'Breakfast';
        elseif ($timeSlot >= '12:00:01' & $timeSlot <= '17:00:00')
            return 'Lunch';
        elseif ($timeSlot >= '17:00:01' & $timeSlot <= '23:59:59')
            return 'Dinner';
        elseif ($timeSlot >= '00:00:00' & $timeSlot <= '04:59:59')
            return 'Dinner';
    }

    /*
     * Find number of friend invited whether in Accept, deny and pending state
     */

    public function InvitationFriendList($my_invitation_record) {
        $count_people = 0;
        $data1 = array();
        $pending = array();
        $denied = array();
        $accepted = array();
        $user_model = new User ();
        foreach ($my_invitation_record as $key_other_people => $val_other_people) {

            if ($val_other_people ['to_id'] != 0) {
                $user_data = $user_model->getUserDetail(array(
                    'columns' => array(
                        'first_name',
                        'last_name'
                    ),
                    'where' => array(
                        'id' => $val_other_people ['to_id']
                    )
                ));
                $user_name = $user_data ['first_name'] . " " . $user_data ['last_name'];
            } else {
                $user_name = $val_other_people ['friend_email'];
            }
            if ($val_other_people ['msg_status'] == '1') {
                $accepted [] = $user_name;
            } elseif ($val_other_people ['msg_status'] == '2') {
                $denied [] = $user_name;
            } elseif ($val_other_people ['msg_status'] == '0') {
                $pending [] = $user_name;
            }

            $data1 ['accepted'] = implode(", ", array_unique($accepted));
            $data1 ['denied'] = implode(", ", array_unique($denied));
            $data1 ['pending'] = implode(", ", array_unique($pending));
        } // end of foreach
        if (!empty($data1 ['accepted'])) {
            $data1 ['accepted'] = $this->filterArraywithAnd($data1 ['accepted']);
        } elseif (!empty($data1 ['pending'])) {
            $data1 ['pending'] = $this->filterArraywithAnd($data1 ['pending']);
        } elseif (!empty($data1 ['denied'])) {
            $data1 ['denied'] = $this->filterArraywithAnd($data1 ['denied']);
        }
        if (!empty($accepted)) {
            unset($pending);
        }
        if (!empty($denied)) {
            unset($denied);
        }
        if (!empty($pending)) {
            unset($pending);
        }
        return $data1;
    }

    public function getHomeReservationDetail($record) {
        $fromUserName = $record ['first_name'];

        $date = StaticFunctions::getFormattedDateTime($record ['time_slot'], 'Y-m-d H:i:s', 'D, M d, Y');
        $time = StaticFunctions::getFormattedDateTime($record ['time_slot'], 'Y-m-d H:i:s', 'h:i A');
        $timeForSlot = StaticFunctions::getFormattedDateTime($record ['time_slot'], 'Y-m-d H:i:s', 'H:i:s');
        $slot = $this->getMealSlot($timeForSlot);
        $myresvation ['reservation_id'] = $record ['id'];
        $myresvation ['restaurant_title'] = $record ['restaurant_name'];
        $myresvation ['reserved_seat'] = $record ['reserved_seats'];
        $myresvation ['reservation_date'] = $date;
        $myresvation ['reservation_time'] = $time;
        $myresvation ['status'] = $record ['status'];
        $myresvation ['from_request'] = $fromUserName;
        $myresvation ['invitation_id'] = isset($record ['invitation_id']) ? $record ['invitation_id'] : '';
        $myresvation ['restaurant_comment'] = isset($record ['restaurant_comment']) ? $this->to_utf8($record ['restaurant_comment']) : '';
        $myresvation ['slot'] = $slot;
        return $myresvation;
    }

    public function arrangeMenuBookmarks($bookmarks) {
        $response = [];
        foreach ($bookmarks as $bookmark) {
            if (!isset($response [$bookmark ['menu_id']])) {
                $response [$bookmark ['menu_id']] ['restaurant_id'] = $bookmark ['restaurant_id'];
                $response [$bookmark ['menu_id']] ['restaurant_name'] = $bookmark ['restaurant_name'];
                $response [$bookmark ['menu_id']] ['menu_id'] = $bookmark ['menu_id'];
                $response [$bookmark ['menu_id']] ['menu_name'] = html_entity_decode(htmlspecialchars_decode($bookmark ['menu_name'], ENT_QUOTES));
                $response [$bookmark ['menu_id']] ['created_on'] = $bookmark ['created_on'];
                $response [$bookmark ['menu_id']] ['res_status'] = ((isset($bookmark['closed']) && $bookmark['closed'] == 0 ) && (isset($bookmark['inactive']) && $bookmark['inactive'] == 0)) ? 1 : 0;
                $response [$bookmark ['menu_id']] ['status'] = isset($bookmark ['status']) ? $bookmark ['status'] : '';
                $response [$bookmark ['menu_id']] ['loved_it'] = false;
                $response [$bookmark ['menu_id']] ['want_it'] = false;
                $response [$bookmark ['menu_id']] ['tried_it'] = false;
                $response [$bookmark ['menu_id']] = $this->setMenuBookmarkType($response [$bookmark ['menu_id']], $bookmark ['type']);
            } else {
                if ($bookmark ['created_on'] > $response [$bookmark ['menu_id']] ['created_on']) {
                    $response [$bookmark ['menu_id']] ['created_on'] = $bookmark ['created_on'];
                }
                $response [$bookmark ['menu_id']] = $this->setMenuBookmarkType($response [$bookmark ['menu_id']], $bookmark ['type']);
            }
        }
        return array_values($response);
    }

    private function setMenuBookmarkType($bookmark, $type) {
        switch ($type) {
            case 'lo' :
                $bookmark ['loved_it'] = true;
                break;
            case 'wi' :
                $bookmark ['want_it'] = true;
                break;
            case 'ti' :
                $bookmark ['tried_it'] = true;
                break;
        }
        return $bookmark;
    }

    public function arrangeRestaurantBookmarks($bookmarks) {
        $response = [];
        foreach ($bookmarks as $bookmark) {
            if (!isset($response [$bookmark ['restaurant_id']])) {
                $response [$bookmark ['restaurant_id']] ['restaurant_id'] = $bookmark ['restaurant_id'];
                $response [$bookmark ['restaurant_id']] ['restaurant_name'] = $bookmark ['restaurant_name'];
                $response [$bookmark ['restaurant_id']] ['created_on'] = $bookmark ['created_on'];
                $response [$bookmark ['restaurant_id']] ['closed'] = $bookmark ['closed'];
                $response [$bookmark ['restaurant_id']] ['inactive'] = $bookmark ['inactive'];
                $response [$bookmark ['restaurant_id']] ['loved_it'] = false;
                $response [$bookmark ['restaurant_id']] ['been_there'] = false;
                $response [$bookmark ['restaurant_id']] ['crave_it'] = false;
                $response [$bookmark ['restaurant_id']] = $this->setRestaurantBookmarkType($response [$bookmark ['restaurant_id']], $bookmark ['type']);
            } else {
                if ($bookmark ['created_on'] > $response [$bookmark ['restaurant_id']] ['created_on']) {
                    $response [$bookmark ['restaurant_id']] ['created_on'] = $bookmark ['created_on'];
                }
                $response [$bookmark ['restaurant_id']] = $this->setRestaurantBookmarkType($response [$bookmark ['restaurant_id']], $bookmark ['type']);
            }
        }
        return array_values($response);
    }

    private function setRestaurantBookmarkType($bookmark, $type) {
        switch ($type) {
            case 'lo' :
                $bookmark ['loved_it'] = true;
                break;
            case 'bt' :
                $bookmark ['been_there'] = true;
                break;
            case 'wl' :
                $bookmark ['crave_it'] = true;
                break;
        }
        return $bookmark;
    }

    /**
     * Get user individual latest paid order
     *
     * @throws \Exception
     * @return array
     */
    public function getIndividualOrder($flag = null) {
        $session = StaticFunctions::getUserSession();
        $userId = $session->getUserId();

        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $this->userCityTimeZone($locationData);
        $stateCode = isset($locationData ['state_code']) ? $locationData ['state_code'] : 'CA';

        if (!$stateCode) {
            throw new \Exception("Invalid State Code. Please select City", 400);
        }

        // Get city Date Time Relatively
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $stateCode
        ));

        $date = $cityDateTime->format("Y-m-d H:i:s");
        $userOrderModel = new UserOrder ();
        $preOrderItemModel = new PreOrderItem ();
        $userOrderDetailModel = new UserOrderDetail ();
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');

        $orderStatus = isset($config ['constants'] ['order_status']) ? $config ['constants'] ['order_status'] : array();
        if ($flag == 'placed') {
            $status [] = $orderStatus [0];
        } elseif ($flag == 'rejected') {
            $status [] = $orderStatus [5];
        } else {
            $status [] = $orderStatus [1];
            $status [] = $orderStatus [2];
            $status [] = $orderStatus [3];
            $status [] = $orderStatus [6];
            $status [] = $orderStatus [8];
        }
        /**
         * Get individual order have latest activity
         */
        $individualOrder = isset($config ['constants'] ['order_type'] ['individual']) ? $config ['constants'] ['order_type'] ['individual'] : 'I';

        $individualData = $userOrderModel->userlastorder($userId, $currentDate, $individualOrder, $status);
        $output = array();
        if (empty($individualData)) {
            $individualData = array();
        } else {
            foreach ($individualData as $individualData) {
                /*
                 * $archiveTime = ""; $deliveryTime = ""; $live = false; $archiveTime = $individualData ['archive_time']; $deliveryTime = $individualData ['delivery_time']; if (! empty ( $individualData ['archive_time'] ) || $individualData ['archive_time'] != NULL) { if (strtotime ( $archiveTime ) >= strtotime ( $date )) { $live = true; } } elseif (strtotime ( $deliveryTime ) >= strtotime ( $date )) { $live = true; } if ($live == true) {
                 */
                $output [] = $individualData;
                // }
            }
        }
        $record = array();
        if (isset($output) && !empty($output)) {

            foreach ($output as $key1 => $value) {
                $dateData [] = StaticFunctions::getFormattedDateTime($value ['delivery_time'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            }
            array_multisort($dateData, SORT_ASC, $output);
            $record = array_slice($output, 0, 1);
            $record = $record [0];
            $individualDataItem = $userOrderDetailModel->getAllOrderDetail(array(
                'columns' => array(
                    'item',
                    'quantity'
                ),
                'where' => array(
                    'user_order_id' => $record ['id']
                // 'user_id' => $userId
                )
            ));
            $individualItemData_new = $this->getPreOrderItem($individualDataItem);
            $record ['order_description'] = $individualItemData_new;
            $record ['restaurants_comments'] = isset($record ['restaurants_comments']) ? $record ['restaurants_comments'] : $record ['crm_comments'];
            $orderTime = StaticFunctions::getFormattedDateTime($record ['created_at'], 'Y-m-d H:i:s', 'Y-m-d');
            $orderDelTime = StaticFunctions::getFormattedDateTime($record ['delivery_time'], 'Y-m-d H:i:s', 'Y-m-d');
            $compOrderTime = StaticFunctions::getFormattedDateTime($record ['created_at'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            $currentDate = StaticFunctions::getFormattedDateTime($date, 'Y-m-d H:i:s', 'Y-m-d');

            if ($orderTime == $currentDate) {
                $record ['order_time'] = $userOrderModel->dateToString($compOrderTime, $date);
            } else {
                $record ['order_time'] = StaticFunctions::getFormattedDateTime($record ['created_at'], 'Y-m-d H:i:s', 'M d, Y');
            }
            if ($orderDelTime == $currentDate) {
                $record ['deliver_on'] = StaticFunctions::getFormattedDateTime($record ['delivery_time'], 'Y-m-d H:i:s', 'h:i A');
            } else {
                $record ['deliver_on'] = StaticFunctions::getFormattedDateTime($record ['delivery_time'], 'Y-m-d H:i:s', 'M d, Y \a\t\ h:i A');
            }
            if ($record ['status'] == $orderStatus [1] || $record ['status'] == $orderStatus [2] || $record ['status'] == $orderStatus [3] || $record ['status'] == $orderStatus [6] || $record ['status'] == $orderStatus [8]) {
                $record ['order_status'] = 'live';
            } elseif ($record ['status'] == $orderStatus [0]) {
                $record ['order_status'] = 'placed';
            } elseif ($record ['status'] == $orderStatus [5]) {
                $record ['order_status'] = 'rejected';
            }
        }

        return $record;
    }

    public function getPendingOrder() {
        $session = StaticFunctions::getUserSession();
        $userId = $session->getUserId();
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');

        $locationData = $session->getUserDetail('selected_location');
        $stateCode = isset($locationData ['state_code']) ? $locationData ['state_code'] : 'CA';

        // Get city Date Time Relatively
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $stateCode
        ));

        $date = $cityDateTime->format("Y-m-d H:i:s");

        $pendingOrder = isset($config ['constants'] ['order_type'] ['orderPending']) ? $config ['constants'] ['order_type'] ['orderPending'] : 0;

        $userPreOrder = new PreOrder ();
        $preOrderRecord = $userPreOrder->userPreOrder($userId, $date, $pendingOrder);

        $preOrderItemModel = new PreOrderItem ();
        $userOrderModel = new UserOrder ();
        $preOrderItem = $preOrderItemModel->getPreOrder(array(
            'columns' => array(
                'item',
                'quantity'
            ),
            'where' => array(
                'pre_order_id' => $preOrderRecord ['id'],
                'user_id' => $userId
            )
        ));

        /*
         * if(empty($preOrderItem)){ return array(); }
         */
        $preOrderItemData = $preOrderItemModel->getPreOrderItem($preOrderItem);
        if (!$preOrderRecord) {
            $preOrderRecord = array();
        } else {
            $preOrderRecord = $preOrderRecord->getArrayCopy();
            $preOrderRecord ['order_description'] = $preOrderItemData;
            $orderTime = StaticFunctions::getFormattedDateTime($preOrderRecord ['created_at'], 'Y-m-d H:i:s', 'Y-m-d');
            $compOrderTime = StaticFunctions::getFormattedDateTime($preOrderRecord ['created_at'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            $currentDate = StaticFunctions::getFormattedDateTime($date, 'Y-m-d H:i:s', 'Y-m-d');

            if ($orderTime == $currentDate) {
                $preOrderRecord ['order_time'] = $userOrderModel->dateToString($compOrderTime, $date);
            } else {
                $preOrderRecord ['order_time'] = StaticFunctions::getFormattedDateTime($preOrderRecord ['created_at'], 'Y-m-d H:i:s', 'M d, Y');
            }
            $preOrderRecord ['deliver_date'] = StaticFunctions::getFormattedDateTime($preOrderRecord ['delivery_time'], 'Y-m-d H:i:s', 'M d, Y');
            $orderAmount = $preOrderRecord ['sub_total'] + $preOrderRecord ['delivery_charges'] + $preOrderRecord ['tax'] + $preOrderRecord ['tip'];
            $preOrderRecord ['total_amount'] = $orderAmount;
            $preOrderRecord ['deliver_on'] = StaticFunctions::getFormattedDateTime($preOrderRecord ['delivery_time'], 'Y-m-d H:i:s', 'h:i A');
            if ($preOrderRecord ['order_status'] == 0) {
                $preOrderRecord ['order_status'] = 'pending_order';
            }
        }
        return $preOrderRecord;
    }

    // ##################INVITATION TO USER#########################################
    public function getReservationDetailInvitationAccepted($userId, $msgStatus, $currentDate = null, $status = null) {
        $ulists = array();
        $invitationAcceptenceRecord = array();
        $reservationData = array();
        $userInvitation = new UserInvitation ();
        $reservationModel = new UserReservation ();
        $invitationAcceptenceRecord = $userInvitation->getAllUserInvitation(array(
            'columns' => array(
                'id',
                'to_id',
                'reservation_id',
                'friend_email',
                'msg_status'
            ),
            'where' => array(
                'to_id' => $userId,
                'msg_status' => $msgStatus
            ),
            'order' => array(
                'created_on DESC'
            )
        ));
        //print_R($invitationAcceptenceRecord);
        if (!empty($invitationAcceptenceRecord)) {
            if ($msgStatus == UserInvitation::ACCEPTED) {
                $statusArray = array(
                    $status ['upcoming'],
                    $status ['rejected'],
                    $status ['confirmed']
                );
            } else {
                $statusArray = array(
                    $status ['upcoming'],
                    $status ['confirmed']
                );
            }
            foreach ($invitationAcceptenceRecord as $value) {

                $reservationIds [] = $value ['reservation_id'];
                $invited_id [$value ['reservation_id']] = $value ['id'];
            }
            $reservationCondition = array(
                'userId' => $userId,
                'currentDate' => $currentDate,
                'status' => $statusArray,
                'reservationIds' => $reservationIds,
                'orderBy' => 'user_reservations.time_slot ASC'
            );
            $reservationData = $reservationModel->getReservationDetails($reservationCondition);

            if ($reservationData) {
                foreach ($reservationData as $key => $value) {
                    $restaurant_id = $value ['restaurant_id'];

                    $reservedOnReadable = StaticFunctions::getFormattedDateTime($value ['reserved_on'], 'Y-m-d H:i:s', 'M d, Y');
                    $reservedOnDate = StaticFunctions::getFormattedDateTime($value ['reserved_on'], 'Y-m-d H:i:s', 'D, M d, Y');
                    $value ['reserved_date'] = StaticFunctions::getFormattedDateTime($value ['time_slot'], 'Y-m-d H:i:s', 'D, M d, Y');
                    $value ['reserved_time'] = StaticFunctions::getFormattedDateTime($value ['time_slot'], 'Y-m-d H:i:s', 'h:i A');
                    $timeSlot = StaticFunctions::getFormattedDateTime($value ['time_slot'], 'Y-m-d H:i:s', 'H:i:s');
                    $value ['type'] = "invitation_accept";
                    $value ['invitation_id'] = $invited_id [$value ['id']];
                    $value ['request_from'] = $value ['first_name'];
                    $endTime = date('Y-m-d H:i:s', strtotime($value ['time_slot'] . ' + 2 hours'));
                    $value ['calendar'] ['start_date'] = (!empty($value ['time_slot'])) ? StaticFunctions::getFormattedDateTime($value ['time_slot'], 'Y-m-d H:i:s', 'D, M d,Y h:i A') : null;
                    $value ['calendar'] ['end_date'] = (!empty($value ['time_slot'])) ? StaticFunctions::getFormattedDateTime($value ['time_slot'], 'Y-m-d H:i:s', 'D, M d,Y h:i A') : null; // (! empty ( $endTime )) ? date ( 'D, M d,Y h:i A', strtotime ( $endTime ) ) : null;
                    $value ['calendar'] ['title'] = (!empty($value ['restaurant_name']) && $value ['restaurant_name'] != null) ? 'Reservation in ' . $value ['restaurant_name'] : null;
                    $value ['calendar'] ['description'] = (!empty($value ['restaurant_name']) && $value ['restaurant_name'] != null && !empty($value ['reserved_seats']) && $value ['reserved_seats'] != null && $value ['reserved_seats'] != null && !empty($value ['reserved_seats'])) ? 'Reservation in ' . $value ['restaurant_name'] . ' for ' . $value ['reserved_seats'] : null;
                    $loc = (!empty($value ['address']) && $value ['address'] != null) ? $value ['address'] : null;
                    $cityName = (!empty($value ['city_name']) && $value ['city_name'] != null) ? $value ['city_name'] : "";
                    $value ['calendar'] ['location'] = $loc . "," . $cityName . "," . $value ['zipcode'];

                    if ($msgStatus == 0) {
                        // # Invited other people ###
                        $value ['slot'] = $this->getMealSlot($timeSlot);
                        $invitedOtherPeople = $userInvitation->getAllUserInvitation(array(
                            'columns' => array(
                                'id',
                                'to_id',
                                'reservation_id',
                                'friend_email',
                                'msg_status'
                            ),
                            'where' => array(
                                'reservation_id' => $value ['id']
                            )
                        ));
                        if ($invitedOtherPeople) {
                            $value ['invited_user'] = $this->InvitationFriendList($invitedOtherPeople);
                        } // end invited other people
                        $value ['type'] = "invited_request";
                    }
                    $value ['msg_status'] = $msgStatus;
                    $ulists [$key] = $value;
                }
            }
        }
        //print_R($ulists);
        return $ulists;
    }

    public function changePassword($data) {
        $userloginModel = new User ();
        $hostname = (isset($data['host_name']) && !empty($data['host_name'])) ? $data['host_name'] : PROTOCOL . SITE_URL;
        $restaurantId = (isset($data['restaurant_id']) && !empty($data['restaurant_id'])) ? $data['restaurant_id'] : false;
        $useraccountModel = new UserAccount ();
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $options = array(
            'columns' => array(
                'id',
                'user_name',
                'first_name',
                'email'
            ),
            'where' => array(
                'email' => $data ['email']
            //'user_source' => 'ws'
            )
        );
        $userDetail = $userloginModel->getUser($options);

        if (!$userDetail) {
            throw new \Exception("We couldn't find that email in our database. Maybe it ran off with another email, got married and changed its name to .net, .org. or some other crazy thing.", 400);
        } else {

            $accountExist = $useraccountModel->CheckUserAccount($userDetail['id']);
            if ($accountExist[0]['accountExist'] == 0) {
                $useraccountModel->user_id = $userDetail['id'];
                $useraccountModel->user_name = $userDetail['user_name'];
                $useraccountModel->first_name = $userDetail['first_name'];
                $useraccountModel->user_source = $data['user_source'];
                $useraccountModel->display_pic_url = 'noimage.jpg';
                $useraccountModel->display_pic_url_normal = 'noimage.jpg';
                $useraccountModel->display_pic_url_large = 'noimage.jpg';
                $useraccountModel->userAccountRegistration();
            }
        }
        try {
            $dbtable = new userdbtable ();
            $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection()->beginTransaction();
            $planePassword = trim($this->generate_verification_code());
            $userloginModel->password = md5($planePassword);
            $userloginModel->id = $userDetail ['id'];
            $password = array(
                'password' => $userloginModel->password
            );
            $userloginModel->update($password);
            $recievers = array(
                $data ['email']
            );
            if ($restaurantId) {
                $template = 'ma_forgot_password';
                $layout = 'ma_default';
                $variables = array(
                    'username' => $userDetail ['first_name'],
                    'password' => $planePassword,
                    'title' => isset($data['restaurant_name']) ? $data['restaurant_name'] . ' Password Recovery Squad Says Panic!' : 'Password Recovery Squad Says Panic!',
                    'restaurant_name' => isset($data['restaurant_name']) ? $data['restaurant_name'] : "",
                    'restaurant_logo' => isset($data['restaurant_logo']) ? $data['restaurant_logo'] : "",
                    'restaurant_address' => isset($data['restaurant_address']) ? $data['restaurant_address'] : "",
                    'facebook_url' => isset($data['facebook_url']) ? $data['facebook_url'] : "",
                    'twitter_url' => isset($data['twitter_url']) ? $data['twitter_url'] : "",
                    'instagram_url' => isset($data['instagram_url']) ? $data['instagram_url'] : "",
                    'hostname' => $hostname
                );
                $subject = isset($data['restaurant_name']) ? $data['restaurant_name'] . ' Password Recovery Squad Says Panic!' : "Password Recovery Squad Says Panic!";
            } else {
                $template = 'forgot-password';
                $layout = 'default_new';
                $variables = array(
                    'username' => $userDetail ['first_name'],
                    'password' => $planePassword,
                    'title' => 'Munch Ado Password Recovery Squad Says Panic!',
                    'hostname' => $webUrl
                );
                $subject = 'Munch Ado Password Recovery Squad Says Panic!';
            }

            // #################

            $emailData = array('recievers' => $data['email'], 'template' => $template, 'layout' => $layout, 'variables' => $variables, 'subject' => $subject);
            // #################
            $this->emailSubscription($emailData);
            $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection()->commit();
        } catch (\Exception $ex) {
            $dbtable->getWriteGateway()->getAdapter()->getDriver()->getConnection()->rollback();
            throw new \Exception("Something apparently went wrong. Password not changed.", 400);
        }
    }

    public function normalLogin($data) {
        $userloginModel = StaticFunctions::getServiceLocator()->get(User::class);
        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location', array());
        $currentDateTime = $this->userCityTimeZone($locationData);
        $form = new LoginForm();

        $commonFunctions = new CommonFunctions();
        $tokeModel = StaticFunctions::getServiceLocator()->get(Auth::class);
        //$referalid = (isset($data['referalid']) && !empty($data['referalid'])) ? $data['referalid'] : false;
        $open_page_type = (isset($data['open_page_type']) && !empty($data['open_page_type'])) ? $data['open_page_type'] : "";
        $refId = (isset($data['refId']) && !empty($data['refId'])) ? $data['refId'] : "";
        if (!filter_var($data ['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("That don't look like any e-mail I ever seen. Maybe the \\\"@\\\" or the \\\".\\\" are in the wrong spot. This isn't cubism, put things where they belong!", 400);
        }

        $form->setData(array(
            'email' => $data ['email'],
            'password' => $data ['password']
        ));
        $token = $data['token'];
        $userloginModel->email = $data ['email'];
        $userloginModel->password = md5($data ['password']);
        $userDetail = $userloginModel->getUserDetail(array(
            'columns' => array(
                'id',
                'first_name',
                'last_name',
                'email',
                'password',
                'status',
                'phone',
                'newsletter_subscribtion',
                'display_pic_url',
                'created_at',
                'update_at',
            ),
            'where' => array(
                'email' => $userloginModel->email
            )
        ));

        if (!$userDetail) {
            throw new \Exception("We couldn't find that email in our database. Maybe it ran off with another email, got married and changed its name to .net, .org. or some other crazy thing.");
        }
        if ($userDetail ['password'] != $userloginModel->password) {
            throw new \Exception("That's not your current password, are you sure you're you?", 400);
        }
        if ($userDetail ['status'] != 1) {
            throw new \Exception("Not allowed to login, contact to administrator.", 400);
        }
        $userProfileImage = $commonFunctions->checkProfileImageUrl($userDetail);
        $tokenExpireTime = $tokeModel->findExpireTimeToken($token);

        if (isset($data['loyality_code']) && !empty($data['loyality_code'])) {
            $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;
            $referralDineMore = array(
                "loyality_code" => $data['loyality_code'],
                "referral_code" => $referralCode,
                "user_id" => $userDetail ['id'],
                "email" => $userDetail ['email'],
                "first_name" => $userDetail ['first_name']
            );

            $this->existUserJoinDineMoreByReferral($referralDineMore, $userDetail ['id']);
        }

        $response = array(
            "id" => $userDetail ['id'],
            "first_name" => $userDetail ['first_name'],
            "last_name" => $userDetail ['last_name'],
            "email" => $userDetail ['email'],
            'phone_number' => $userDetail ['phone'],
            'is_SubscribeToNewsLetter' => $userDetail ['newsletter_subscribtion'],
            'profile_image_url' => $userProfileImage ['display_pic_url'],
            'token_expire_time' => $tokenExpireTime ? date('Y-m-d H:i:s', $tokenExpireTime) : '',
        );
        $userloginModel->id = $userDetail ['id'];

        if (strtotime($userDetail['created_at']) == strtotime($userDetail['update_at'])) {

            $cleverTap = array(
                "user_id" => $userDetail ['id'],
                "name" => (isset($userDetail ['last_name']) && !empty($userDetail ['last_name'])) ? $userDetail['first_name'] . " " . $userDetail ['last_name'] : $userDetail['first_name'],
                "email" => $userDetail['email'],
                "identity" => $userDetail['email'],
                "eventname" => "signed_to_app",
                "is_register" => "yes",
                "date" => $currentDateTime,
                "event" => 1,
            );
            $this->createQueue($cleverTap, 'clevertap');
        }


        $data = array(
            'last_login' => $currentDateTime,
            'update_at' => $currentDateTime
        );
        $userloginModel->update($data);

        $session->setUserId($userDetail ['id']);
        $data = array(
            'email' => $userDetail ['email']
        );
        $session->setUserDetail($data);
        $session->save();

        ########### Associate user through deeplink ##############
        $userId = $userDetail ['id'];
        $userEmail = $userDetail ['email'];
        if (!empty($open_page_type)) {
            $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
        }
        ##########################################
        return $response;

//            $functions = new FormErrorFunctions ();
//            $error = $functions->getLoginFormError($form->getMessages());
//            throw new \Exception($error);
    }

    private function generate_verification_code() {
        $length = 6;
        $verification_code = '';
        list ( $usec, $sec ) = explode(' ', microtime());
        mt_srand((float) $sec + ((float) $usec * 100000));
        $inputs = array_merge(range('z', 'a'), range(0, 9), range('A', 'Z'));
        for ($i = 0; $i < $length; $i ++) {
            $verification_code .= $inputs {mt_rand(0, 61)};
        }
        return $verification_code;
    }

    public function googleLogin($data) {
        $userModel = StaticFunctions::getServiceLocator()->get(User::class);
        $userAccountModel = StaticFunctions::getServiceLocator()->get(UserAccount::class);

        if (!isset($data ['displayName']) && empty($data ['displayName'])) {
            throw new \Exception('User display name is not valid', 400);
        }

        if (!isset($data ['givenName']) && empty($data ['givenName'])) {
            throw new \Exception('User given name is not valid', 400);
        }

        if (!isset($data ['gp_uid']) && empty($data ['gp_uid'])) {
            throw new \Exception('Google user id is required', 400);
        }


        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location', array());
        $currentDateTime = $this->userCityTimeZone($locationData);
        $cityId = isset($locationData ['city_id']) ? $locationData ['city_id'] : "";
        $userModel->city_id = $cityId;
        $userAccountModel->user_name = isset($data ['displayName']) ? $data ['displayName'] : '';
        $userAccountModel->first_name = isset($data ['givenName']) ? $data ['givenName'] : '';
        $userAccountModel->last_name = isset($data ['family_name']) ? $data ['family_name'] : '';
        $userAccountModel->user_source = 'gp';
        $userModel->last_login = $currentDateTime;
        $userAccountModel->access_token = isset($data ['accessToken']) ? $data ['accessToken'] : '';
        $userAccountModel->session_token = isset($data ['gp_uid']) ? $data ['gp_uid'] : '';
        $userModel->update_at = $currentDateTime;
        $userModel->display_pic_url = isset($data ['image_url']) ? $data ['image_url'] : '';
        $userModel->display_pic_url_normal = isset($data ['image_url']) ? $data ['image_url'] : '';
        $userModel->display_pic_url_large = isset($data ['image_url']) ? $data ['image_url'] : '';
        $userModel->email = isset($data ['email']) ? $data ['email'] : '';
        $userModel->phone = isset($data ['phone']) ? $data ['phone'] : '';
        $userModel->newsletter_subscribtion = 0;
        $userModel->status = 1;
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $referalid = (isset($data['referalid']) && !empty($data['referalid'])) ? $data['referalid'] : false;
        $open_page_type = (isset($data['open_page_type']) && !empty($data['open_page_type'])) ? $data['open_page_type'] : "";
        $refId = (isset($data['refId']) && !empty($data['refId'])) ? $data['refId'] : "";
        $userLoginDetail = array();
        $userLoginDetail ['first_name'] = $userAccountModel->first_name;
        $userLoginDetail ['last_name'] = $userAccountModel->last_name;
        $userLoginDetail ['phone'] = $userModel->phone;
        $userLoginDetail ['newsletter_subscribtion'] = $userModel->newsletter_subscribtion;
        $userLoginDetail ['profile_image_url'] = $userModel->display_pic_url;
        $userLoginDetail ['email'] = $userModel->email;

        $joins = array();
        $joins [] = array(
            'name' => array(
                'ua' => 'user_account'
            ),
            'on' => 'users.id = ua.user_id',
            'columns' => array(
                'user_source',
                'access_token',
                'session_token'
            ),
            'type' => 'inner'
        );

        if (isset($userModel->email)) {
            $options = array(
                'columns' => array(
                    '*'
                ),
                'where' => array('users.email' => $userModel->email),
                'joins' => $joins,
            );
        } else {
            $options = array(
                'columns' => array(
                    '*'
                ),
                'where' => array('ua.session_token' => $userAccountModel->session_token),
                'joins' => $joins,
            );
        }
        $userDetail = $userModel->getUserDetail($options);
        $session = StaticFunctions::getUserSession();

        if ($userDetail) {
            $userModel->id = $userDetail ['id'];
            $userModel->created_at = $userDetail ['created_at'];
            $userModel->password = $userDetail ['password'];
            $userModel->points = $userDetail ['points'];
            $userModel->display_pic_url = $userDetail ['display_pic_url'];
            //$userModel->userRegistration();
            $session->setUserId($userDetail ['id']);
            $userLoginDetail["first_name"] = $userDetail['first_name'];
            $userLoginDetail["last_name"] = $userDetail['last_name'];
            $userLoginDetail["id"] = $userDetail ['id'];
            $userLoginDetail['phone_number'] = $userDetail['phone'];
            $userLoginDetail['email'] = $userDetail['email'];
            $userAccountModel->user_id = $userDetail ['id'];
            $userAccountModel->userAccountRegistration();

            if (isset($data['loyality_code']) && !empty($data['loyality_code'])) {
                $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;
                $referralDineMore = array(
                    "loyality_code" => $data['loyality_code'],
                    "referral_code" => $referralCode,
                    "user_id" => $userDetail ['id'],
                    "email" => $userDetail ['email'],
                    "first_name" => $userDetail ['first_name']
                );

                $this->existUserJoinDineMoreByReferral($referralDineMore, $userDetail ['id']);
            }
            $data = array(
                'email' => $userDetail ['email']
            );
            $session->setUserDetail($data);
            $session->save();
            ########### Associate user through deeplink ##############
            $userId = $userDetail ['id'];
            $this->userId = $userDetail['id'];
            $userEmail = $userDetail ['email'];
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            $userLoginDetail['dine_and_more'] = (int) 0;
            if ($this->isRegisterWithAnyRestaurant()) {
                $userLoginDetail['dine_and_more'] = (int) 1;
            }
            ########################################## 
            $userLoginDetail['previously_registered'] = (int) 1;
            if (strtotime($userDetail['created_at']) == strtotime($userDetail['update_at'])) {
                $cleverTap = array(
                    "user_id" => $userDetail ['id'],
                    "name" => (isset($userDetail ['last_name']) && !empty($userDetail ['last_name'])) ? $userDetail['first_name'] . " " . $userDetail ['last_name'] : $userDetail['first_name'],
                    "email" => $userDetail['email'],
                    "identity" => $userDetail['email'],
                    "eventname" => "signed_to_app",
                    "is_register" => "yes",
                    "date" => $currentDateTime,
                    "event" => 1,
                );
                $this->createQueue($cleverTap, 'clevertap');
            }
            $userModel->update(array(
                'last_login' => $currentDateTime,
                'update_at' => $currentDateTime
            ));
            return $userLoginDetail;
        } else {
            if (empty($data ['email'])) {
                throw new \Exception("gp", 400);
            }
            //check email id exist or not 
            $emailExist = $userModel->getUserDetail(array(
                'columns' => array(
                    'countemail' => new \Zend\Db\Sql\Expression('COUNT(*)'),
                ),
                'where' => array(
                    'email' => $userModel->email
                )
            ));
            if ($emailExist['countemail'] == 1) {
                throw new \Exception("Email address already exist", 400);
            }
            $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;
            $loyalityCode = (isset($data['loyality_code']) && !empty($data['loyality_code'])) ? $data['loyality_code'] : "";

            ############## Loyality Program Registration code validation #############
            if ($loyalityCode) {
                if (!$this->parseLoyaltyCode($loyalityCode)) {
                    throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.");
                }
            }
            ##########################################################################
            //check email id exist or not 
            $userModel->user_name = isset($data ['displayName']) ? $data ['displayName'] : '';
            $userModel->first_name = isset($data ['givenName']) ? $data ['givenName'] : '';
            $userModel->last_name = isset($data ['familyName']) ? $data ['familyName'] : '';
            $userModel->created_at = $currentDateTime;
            $userDetail = $userModel->userRegistration();
            $userAccountModel->user_id = $userDetail ['id'];
            $userAccountModel->userAccountRegistration();
            $session->setUserId($userModel->id);
            $data = array(
                'email' => $userDetail ['email']
            );
            $session->setUserDetail($data);
            $session->save();
            $userLoginDetail['id'] = $userModel->id;
          
            $points = $this->getAllocatedPoints('socialRegister');
            $message = 'All life is a game. Here are 100 points to get you ahead of the game. Don\'t worry, it\'s not cheating.';
            $this->givePoints($points, $userModel->id, $message);
            $feed_name = $userModel->first_name . ' ' . $userModel->last_name;
            $feed = array(
                'user_id' => $userModel->id,
                'user_email' => $userDetail ['email'],
                'user_name' => ucfirst($feed_name)
            );
            $replacementData = array('message' => 'test');
            $otherReplacementData = array('user_name' => ucfirst($feed_name));

            $commonFunction = new \MCommons\CommonFunctions();
            $commonFunction->addActivityFeed($feed, 53, $replacementData, $otherReplacementData);


            #############################################################################
            ############### Assign Promocode ##################
            $this->assignPromocodeOnFirstRegistration($userModel->id, $userModel->user_name, $userModel->email);
            ###################################################
            ############## Loyality Program Registration #############  
            
            $register_notif_count = 1;
            if ($loyalityCode) {
                $this->registerRestaurantServer();
                $this->userId = $userModel->id;
                $this->first_name = $userModel->first_name;
                $this->email = $userDetail ['email'];
                $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
                $this->loyaltyCode = $loyalityCode;
                $this->mailSmsRegistrationPassword($template);
                $this->dineAndMoreAwards("awardsregistration");
                $register_notif_count = 2;
            } 

            ###################################################
            ########## Notification to user on first Registration ########
            $notificationMsg = 'Welcome to Munch Ado! From now on, we’ll be helping you get from hangry to satisfied.';
            $channel = "mymunchado_" . $userModel->id;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userModel->id,
                "type" => 'registration',
                "restaurantId" => '0',
                'curDate' => $currentDateTime
            );
//            $userNotificationModel = new \User\Model\UserNotification();
//            $response = $userNotificationModel->createPubNubNotification($notificationArray);
//            $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);
            ######## Send Mail to user for registration #########

            if (!$loyalityCode || strtoupper($loyalityCode) === MUNCHADO_DINE_MORE_CODE) {
                $template = 'user-registration';
                $layout = 'email-layout/default_register';
                $variables = array(
                    'username' => $userLoginDetail['first_name'],
                    'hostname' => $webUrl
                );
                $mailData = array('recievers' => $userLoginDetail['email'], 'layout' => $layout, 'template' => $template, 'variables' => $variables);
                $this->sendRegistrationEmail($mailData);
            }
            if ($referalid) {
                $this->invitationAccepted($referalid, $userLoginDetail['email'], true);
            }
            $this->createSettings($userLoginDetail['id'], $userLoginDetail['newsletter_subscribtion']);

            ######### Intigration of user reffer invitation ############
            if ($referralCode) {
                $this->saveReferredUserInviterData($userLoginDetail['id'], $referralCode);
            }
            ############################################################
            ########### Associate user through deeplink ##############
            $userId = $userModel->id;
            $userEmail = $userDetail ['email'];
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            ########################################## 
            $userLoginDetail['previously_registered'] = (int) 0;
            $userLoginDetail['register_notif_count'] = $register_notif_count;
            $clevertapData = array(
                "user_id" => $userModel->id,
                "name" => ($userModel->last_name) ? $userModel->first_name . " " . $userModel->last_name : $userModel->first_name,
                "email" => $userModel->email,
                "currentDate" => $currentDateTime,
                "source" => $userAccountModel->user_source,
                "loyalitycode" => ($loyalityCode) ? $loyalityCode : false,
                "restname" => ($loyalityCode) ? $this->restaurant_name : "",
                "restid" => ($loyalityCode) ? $this->restaurantId : "",
                "eventname" => ($loyalityCode) ? "dine_and_more" : "general",
            );
            if ($referralCode) {
                $clevertapData['refferralPoint'] = $this->referralPoint;
            }
            $this->clevertapRegistrationEvent($clevertapData);
            return $userLoginDetail;
        }
    }

    public function facebookLogin($data) {
        $userModel = StaticFunctions::getServiceLocator()->get(User::class);
        $userAccountModel = StaticFunctions::getServiceLocator()->get(UserAccount::class);

        if (!isset($data ['fb_uid']) && empty($data ['fb_uid'])) {
            throw new \Exception('Facebook user id is required', 400);
        }

        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');

        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location', array());
        $currentDateTime = $this->userCityTimeZone($locationData);
        $cityId = isset($locationData ['city_id']) ? $locationData ['city_id'] : "";
        $userModel->city_id = $cityId;
        $fname = "";
        $lname = "";
        if (isset($data ['displayName']) && !empty($data ['displayName'])) {
            $username = explode(" ", $data ['displayName']);
            $fname = isset($username[0]) ? $username[0] : "";
            $lname = isset($username[1]) ? $username[1] : "";
        }
        $userAccountModel->user_name = isset($data ['displayName']) ? $data ['displayName'] : '';
        $userAccountModel->first_name = (isset($data ['first_name']) && !empty($data['first_name'])) ? $data['first_name'] : $fname;
        $userAccountModel->last_name = (isset($data ['last_name']) && !empty($data['last_name'])) ? $data['last_name'] : $lname;
        $userAccountModel->user_source = 'fb';
        $userModel->last_login = $currentDateTime;
        $userAccountModel->access_token = isset($data ['accessToken']) ? $data ['accessToken'] : '';
        $userAccountModel->session_token = isset($data ['fb_uid']) ? $data ['fb_uid'] : '';
        $userModel->update_at = $currentDateTime;
        $userModel->display_pic_url = \Facebook\FacebookClient::BASE_GRAPH_URL . '/' . $data ['fb_uid'] . '/picture?type=normal';
        $userModel->display_pic_url_normal = \Facebook\FacebookClient::BASE_GRAPH_URL . '/' . $data ['fb_uid'] . '/picture?type=normal';
        $userModel->display_pic_url_large = \Facebook\FacebookClient::BASE_GRAPH_URL . '/' . $data ['fb_uid'] . '/picture?type=normal';
        $userModel->email = isset($data ['email']) ? $data ['email'] : '';
        $userModel->phone = isset($data ['phone']) ? $data ['phone'] : '';
        $userModel->newsletter_subscribtion = 0;
        $userModel->status = 1;
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $referalid = (isset($data['referalid']) && !empty($data['referalid'])) ? $data['referalid'] : false;
        $open_page_type = (isset($data['open_page_type']) && !empty($data['open_page_type'])) ? $data['open_page_type'] : "";
        $refId = (isset($data['refId']) && !empty($data['refId'])) ? $data['refId'] : "";
        $userLoginDetail = array();
        $userLoginDetail ['first_name'] = $userAccountModel->first_name;
        $userLoginDetail ['last_name'] = $userAccountModel->last_name;
        $userLoginDetail ['phone'] = $userModel->phone;
        $userLoginDetail ['newsletter_subscribtion'] = $userModel->newsletter_subscribtion;
        $userLoginDetail ['profile_image_url'] = $userModel->display_pic_url;
        $userLoginDetail ['email'] = $userModel->email;
        $joins = array();
        $joins [] = array(
            'name' => array(
                'ua' => 'user_account'
            ),
            'on' => 'users.id = ua.user_id',
            'columns' => array(
                'user_source',
                'access_token',
                'session_token'
            ),
            'type' => 'inner'
        );
        if (isset($userModel->email)) {
            $options = array(
                'columns' => array(
                    '*'
                ),
                'where' => array('users.email' => $userModel->email),
                'joins' => $joins,
            );
        } else {
            $options = array(
                'columns' => array(
                    '*'
                ),
                'where' => array('ua.session_token' => $userAccountModel->session_token),
                'joins' => $joins,
            );
        }
        $userDetail = $userModel->getUserDetail($options);
        if ($userDetail) {
            $userModel->id = $userDetail ['id'];
            $userModel->created_at = $userDetail ['created_at'];
            $userModel->password = $userDetail ['password'];
            $userModel->points = $userDetail ['points'];
            $userModel->display_pic_url = $userDetail ['display_pic_url'];

            if (isset($data['loyality_code']) && !empty($data['loyality_code'])) {
                $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;
                $referralDineMore = array(
                    "loyality_code" => $data['loyality_code'],
                    "referral_code" => $referralCode,
                    "user_id" => $userDetail ['id'],
                    "email" => $userDetail ['email'],
                    "first_name" => $userDetail ['first_name']
                );

                $this->existUserJoinDineMoreByReferral($referralDineMore, $userDetail ['id']);
            }

            //$userModel->userRegistration();
            $session->setUserId($userDetail ['id']);
            $userLoginDetail["first_name"] = $userDetail ['first_name'];
            $userLoginDetail["last_name"] = $userDetail ['last_name'];
            $userLoginDetail["id"] = $userDetail ['id'];
            $userLoginDetail["phone_number"] = $userDetail['phone'];
            $userLoginDetail["email"] = $userDetail['email'];
            $userAccountModel->user_id = $userDetail ['id'];
            $userAccountModel->userAccountRegistration();
            $data = array(
                'email' => $userDetail ['email']
            );
            $session->setUserDetail($data);
            $session->save();
            ########### Associate user through deeplink ##############
            $userId = $userDetail ['id'];
            $this->userId = $userDetail ['id'];
            $userEmail = $userDetail ['email'];
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            ########################################## 
            $userLoginDetail["dine_and_more"] = (int) 0;
            if ($this->isRegisterWithAnyRestaurant()) {
                $userLoginDetail["dine_and_more"] = (int) 1;
            }
            $userLoginDetail['previously_registered'] = (int) 1;
            if (strtotime($userDetail['created_at']) == strtotime($userDetail['update_at'])) {
                $cleverTap = array(
                    "user_id" => $userDetail ['id'],
                    "name" => (isset($userDetail ['last_name']) && !empty($userDetail ['last_name'])) ? $userDetail['first_name'] . " " . $userDetail ['last_name'] : $userDetail['first_name'],
                    "email" => $userDetail['email'],
                    "identity" => $userDetail['email'],
                    "eventname" => "signed_to_app",
                    "is_register" => "yes",
                    "date" => $currentDateTime,
                    "event" => 1,
                );
                $this->createQueue($cleverTap, 'clevertap');
            }

            $userModel->update(array(
                'last_login' => $currentDateTime,
                'update_at' => $currentDateTime
            ));
            return $userLoginDetail;
        } else {
            if (empty($data ['email'])) {
                throw new \Exception("fb", 400);
            }
            //check email id exist or not 
            $emailExist = $userModel->getUserDetail(array(
                'columns' => array(
                    'countemail' => new \Zend\Db\Sql\Expression('COUNT(*)'),
                ),
                'where' => array(
                    'email' => $userModel->email
                )
            ));
            if ($emailExist['countemail'] == 1) {
                throw new \Exception("Email address already exist", 400);
            }

            $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;

            $loyalityCode = (isset($data['loyality_code']) && !empty($data['loyality_code'])) ? $data['loyality_code'] : "";

            ############## Loyality Program Registration code validation #############
            if ($loyalityCode) {
                if (!$this->parseLoyaltyCode($loyalityCode)) {
                    throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.");
                }
            }

            //check email id exist or not 
            $userModel->user_name = isset($data ['displayName']) ? $data ['displayName'] : '';
            $userModel->first_name = isset($data ['first_name']) ? $data ['first_name'] : '';
            $userModel->last_name = isset($data ['last_name']) ? $data ['last_name'] : '';
            $userModel->created_at = $currentDateTime;
            $userDetail = $userModel->userRegistration();
            $userAccountModel->user_id = $userDetail ['id'];
            $userAccountModel->userAccountRegistration();
            $session->setUserId($userModel->id);
            $data = array(
                'email' => $userDetail ['email']
            );

            $session->setUserDetail($data);
            $session->save();
            $userLoginDetail['id'] = $userModel->id;
            ####################### Assign user for registration #######################
//            $domain = $this->checkDomain($userModel->email);
//            if ($domain === 'edu') {
//                $points = $this->getAllocatedPoints('eduRegister');
//                $message = 'All life is a game. Here are 400 points to get you ahead of the game. Don\'t worry, it\'s not cheating.';
//                $this->givePoints($points, $userModel->id, $message);
//            } else {
            $points = $this->getAllocatedPoints('socialRegister');
            $message = 'All life is a game. Here are 100 points to get you ahead of the game. Don\'t worry, it\'s not cheating.';
            $this->givePoints($points, $userModel->id, $message);
            $feed_name = $userModel->first_name . ' ' . $userModel->last_name;
            $feed = array(
                'user_id' => $userModel->id,
                'user_email' => $userDetail ['email'],
                'user_name' => ucfirst($feed_name)
            );
            $replacementData = array('message' => 'test');
            $otherReplacementData = array('user_name' => ucfirst($feed_name));

            $commonFunction = new \MCommons\CommonFunctions();
            $activityFeed = $commonFunction->addActivityFeed($feed, 53, $replacementData, $otherReplacementData);

            #############################################################################
            ############### Assign Promocode ##################
            $this->assignPromocodeOnFirstRegistration($userModel->id, $userModel->user_name, $userModel->email);
            ###################################################
            ############## Loyality Program Registration #############  
            $register_notif_count = 1;
            if ($loyalityCode) {
                $this->registerRestaurantServer();
                $this->userId = $userModel->id;
                $this->first_name = $userModel->first_name;
                $this->email = $userDetail ['email'];
                $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
                $this->loyaltyCode = $loyalityCode;
                $this->mailSmsRegistrationPassword($template);
                $this->dineAndMoreAwards("awardsregistration");
                $register_notif_count = 2;
            }
                        
            ########### Notification to user on first Registration ########
            //$notificationMsg = 'Welcome to Munchado!';
            $notificationMsg = 'Welcome to Munch Ado! From now on, we’ll be helping you get from hangry to satisfied.';
            $channel = "mymunchado_" . $userModel->id;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userModel->id,
                "type" => 'registration',
                "restaurantId" => '0',
                'curDate' => $currentDateTime
            );
//            $userNotificationModel = StaticFunctions::getServiceLocator()->get(UserNotification::class);
//            $response = $userNotificationModel->createPubNubNotification($notificationArray);
//            $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);

            if (!$loyalityCode || strtoupper($loyalityCode) === MUNCHADO_DINE_MORE_CODE) {
                $template = 'user-registration';
                $layout = 'email-layout/default_register';
                $variables = array(
                    'username' => $userLoginDetail['first_name'],
                    'hostname' => $webUrl
                );
                $mailData = array('recievers' => $userLoginDetail['email'], 'layout' => $layout, 'template' => $template, 'variables' => $variables);
                $this->sendRegistrationEmail($mailData);
            }
            if ($referalid) {
                $this->invitationAccepted($referalid, $userLoginDetail['email'], true);
            }

            $this->createSettings($userLoginDetail['id'], $userLoginDetail['newsletter_subscribtion']);

            ######### Intigration of user reffer invitation ############
            if ($referralCode) {
                $this->saveReferredUserInviterData($userLoginDetail['id'], $referralCode);
            }
            ############################################################
            ########### Associate user through deeplink ##############
            $userId = $userModel->id;
            $userEmail = $userDetail ['email'];
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            ########################################## 
            $userLoginDetail['previously_registered'] = (int) 0;
            $userLoginDetail['register_notif_count'] = $register_notif_count;
            $clevertapData = array(
                "user_id" => $userModel->id,
                "name" => ($userModel->last_name) ? $userModel->first_name . " " . $userModel->last_name : $userModel->first_name,
                "email" => $userModel->email,
                "currentDate" => $currentDateTime,
                "source" => $userAccountModel->user_source,
                "loyalitycode" => ($loyalityCode) ? $loyalityCode : false,
                "restname" => ($loyalityCode) ? $this->restaurant_name : "",
                "restid" => ($loyalityCode) ? $this->restaurantId : "",
                "eventname" => ($loyalityCode) ? "dine_and_more" : "general",
            );
            if ($referralCode) {
                $clevertapData['refferralPoint'] = $this->referralPoint;
            }
            $this->clevertapRegistrationEvent($clevertapData);
            
            return $userLoginDetail;
        }
    }

    public function registerUser($data) {
        try {
            $userDetail = array();

            $userloginModel = StaticFunctions::getServiceLocator()->get(User::class);
            $userAccountModel = StaticFunctions::getServiceLocator()->get(UserAccount::class);
            if (!isset($data ['first_name']) || empty($data ['first_name'])) {
                throw new \Exception("First name can not be empty.", 400);
            } else {
                $userloginModel->first_name = $data ['first_name'];
            }
            if (!isset($data ['email']) || empty($data ['email'])) {
                throw new \Exception("Email can not be empty.", 400);
            } else {
                $userloginModel->email = $data ['email'];
            }

            if (!isset($data ['password']) || empty($data ['password'])) {
                throw new \Exception("Password can not be empty.", 400);
            } else {
                $userloginModel->password = md5($data ['password']);
            }

            if (!isset($data ['accept_toc'])) {
                throw new \Exception("Required to accept term & condition.", 400);
            }
            if (isset($data['referral_code']) && !empty($data['referral_code'])) {
                $this->isReferralCodeValid($data['referral_code'], $userloginModel);
            }

            $loyalityCode = (isset($data['loyality_code']) && !empty($data['loyality_code'])) ? $data['loyality_code'] : "";

            ############## Loyality Program Registration code validation #############
            if ($loyalityCode) {
                if (!$this->parseLoyaltyCode($loyalityCode)) {
                    throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.", 400);
                }
            }

            $options = array(
                'where' => array(
                    'email' => $userloginModel->email
                )
            );


            $userDetail = $userloginModel->getUserDetail($options);
            if (!empty($userDetail)) {
                throw new \Exception("Email is already registered.", 400);
            }

            unset($data['token']);
            $sl = StaticFunctions::getServiceLocator();
            $userNotificationModel = $sl->get(UserNotification::class);
            $session = StaticFunctions::getUserSession();
            $locationData = $session->getUserDetail('selected_location');
            $cityId = isset($locationData ['city_id']) ? $locationData ['city_id'] : "";
            $this->cityId = $cityId;
            $userloginModel->city_id = $cityId;
            $currentDate = $this->userCityTimeZone($locationData);
            $this->currentDateTimeUnixTimeStamp = strtotime($currentDate);
            $config = $sl->get('Config');
            $webUrl = PROTOCOL . $config['constants']['web_url'];
            $open_page_type = (isset($data['open_page_type']) && !empty($data['open_page_type'])) ? $data['open_page_type'] : "";
            $refId = (isset($data['refId']) && !empty($data['refId'])) ? $data['refId'] : "";
            $referalid = (isset($data['referalid']) && !empty($data['referalid'])) ? $data['referalid'] : false;
            $userloginModel->last_name = (isset($data ['last_name'])) ? $data ['last_name'] : '';
            $userloginModel->newsletter_subscribtion = (isset($data ['newsletter_subscription'])) ? $data ['newsletter_subscription'] : 0;
            $userloginModel->created_at = $currentDate;
            $userloginModel->update_at = $currentDate;
            $userloginModel->last_login = $currentDate;
            $userloginModel->order_msg_status = '';
            $userloginModel->status = 1;

            $userloginModel->bp_status = 0;
            $response1 = $userloginModel->userRegistration();

            if (!$response1) {
                throw new \Exception("Registration failed.", 400);
            }
            $userAccountModel->user_source = isset($data['source']) ? $data['source'] : 'iOS';
            $userAccountModel->user_id = $userloginModel->id;
            $userAccountModel->userAccountRegistration();
            ####################### Assign points user for registration #######################
//            if ($domain === 'edu') {
//                $points = $this->getAllocatedPoints('eduRegister');
//                $message = 'Welcome to Munch Ado! You\'ll need loyalty points to have the most fun, here take 400. Use them wisely!.';
//                $this->givePoints($points, $userloginModel->id, $message);
//            } else {
            $points = $this->getAllocatedPoints('normalRegister');
            $points['type'] = "normalRegister";
            $message = "All life is a game. Here are 100 points to get you ahead of the game. Don't worry, it's not cheating.";
            $this->givePoints($points, $userloginModel->id, $message);


            $feed_name = $userloginModel->first_name . ' ' . $userloginModel->last_name;
            $feed = array(
                'user_id' => $userloginModel->id,
                'user_email' => $userDetail ['email'],
                'user_name' => ucfirst($feed_name)
            );
            $replacementData = array('message' => 'test');
            $otherReplacementData = array('user_name' => ucfirst($feed_name));

            $commonFunction = new CommonFunctions();
            $activityFeed = $commonFunction->addActivityFeed($feed, 53, $replacementData, $otherReplacementData);
            #############################################################################
            ############### Assign Promocode ##################
            $this->assignPromocodeOnFirstRegistration($userloginModel->id, $userloginModel->first_name, $userloginModel->email);
            ###################################################
            #
            ########## Notification to user on first Registration ########
            //$notificationMsg = 'Welcome to Munchado!';
            $notificationMsg = 'Welcome to Munch Ado! From now on, we’ll be helping you get from hangry to satisfied.';
            $channel = "mymunchado_" . $userloginModel->id;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userloginModel->id,
                "type" => 'registration',
                "restaurantId" => '0',
                'curDate' => $currentDate
            );
            // $response = $userNotificationModel->createPubNubNotification($notificationArray);
            // $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);
            ######## Send Mail to user for registration #########
//            if ($domain === 'edu') {
//                $template = 'edu_subscriber';
//                $layout = 'default_new';
//                $subject = 'Welcome Friend!';
//                $variables = array('hostname' => $webUrl);
//                $mailData = array('recievers' => $userloginModel->email, 'template' => $template, 'layout' => $layout, 'variables' => $variables, 'subject' => $subject);
//                $this->emailSubscription($mailData);
//            } else {
            if (!$loyalityCode || strtoupper($loyalityCode) === MUNCHADO_DINE_MORE_CODE) {
                $template = 'user-registration';
                $layout = 'email-layout/default_register';
                $variables = array(
                    'username' => $userloginModel->first_name,
                    'hostname' => $webUrl
                );
                $mailData = array('recievers' => $userloginModel->email, 'layout' => $layout, 'template' => $template, 'variables' => $variables);
                $this->sendRegistrationEmail($mailData);
            }

            if ($referalid) {
                $this->invitationAccepted($referalid, $userloginModel->email, true);
            }
            $this->createSettings($userloginModel->id, $userloginModel->newsletter_subscribtion);

            $response1 = array_intersect_key($response1, array_flip(array(
                'id',
                'first_name',
                'last_name',
                'email'
            )));

            ######### Intigration of user reffer invitation ############
            $feed = array(
                'user_id' => $userloginModel->id,
                'user_name' => ucfirst($userloginModel->first_name),
                'restaurant_name' => ucfirst($this->restaurant_name),
                'restaurant_id' => $this->restaurantId
            );

            if (isset($data['referral_code']) && !empty($data['referral_code'])) {
                $this->saveReferredUserInviterData($userloginModel->id, $data['referral_code']);
                $commonFunction->addActivityFeed($feed, 68, array('restaurant_name' => ucfirst($this->restaurant_name)), array('restaurant_name' => ucfirst($this->restaurant_name), 'user_name' => ucfirst($userloginModel->first_name)));
            } else if ($loyalityCode) {
                $commonFunction->addActivityFeed($feed, 68, array('restaurant_name' => ucfirst($this->restaurant_name)), array('restaurant_name' => ucfirst($this->restaurant_name), 'user_name' => ucfirst($userloginModel->first_name)));
            }
            ############################################################

            $session = StaticFunctions::getUserSession();
            $session->setUserId($userloginModel->id);
            $session->save();
            $userId = $userloginModel->id;
            $userEmail = $userloginModel->email;
            $register_notif_count = 1;
            ############## Loyality Program Registration #############           
            if ($loyalityCode) {
                $this->registerRestaurantServer();
                $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
                $this->first_name = $userloginModel->first_name;
                $this->email = $userloginModel->email;
                $this->userId = $userId;
                $this->dineAndMoreAwards("awardsregistration");
                $this->loyaltyCode = $loyalityCode;
                $register_notif_count = 2;
                $this->mailSmsRegistrationPassword($template);
            }
            ##########################################################

            if ($loyalityCode) {
                $point = 200;
                $totalpoint = (int) $this->userTotalPoint($userId);
            } else {
                $point = 100;
                $totalpoint = (int) $this->userTotalPoint($userId);
            }

            $clevertapData = array(
                "user_id" => $userloginModel->id,
                "name" => ($userloginModel->last_name) ? $userloginModel->first_name . " " . $userloginModel->last_name : $userloginModel->first_name,
                "email" => $userloginModel->email,
                "currentDate" => $currentDate,
                "source" => $userAccountModel->user_source,
                "loyalitycode" => ($loyalityCode) ? $loyalityCode : false,
                "restname" => ($this->loyaltyCode) ? $this->restaurant_name : "",
                "restid" => ($this->loyaltyCode) ? $this->restaurantId : "",
                "eventname" => ($loyalityCode) ? "dine_and_more" : "general",
            );
            if (isset($data['referral_code']) && !empty($data['referral_code'])) {
                $clevertapData['refferralPoint'] = $userFunctions->referralPoint;
            }
            $this->clevertapRegistrationEvent($clevertapData);
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            $response1['register_notif_count'] = $register_notif_count;
            $response1['total_point'] = $totalpoint;
            return $response1;
        } catch (\Exception $e) {
            $munchLogger = StaticFunctions::getServiceLocator()->get(\MUtility\MunchLogger::class);
            $munchLogger::writeLog($e, 1, 'Something Went Wrong to register new user:' . $e->getMessage());
            throw new \Exception($e->getMessage(), 400);
        }
    }

    public function generate_reservation_receipt_number($type, $state_code) {
        $timestamp = date('mdhis');
        $keys = rand(0, 9);
        $randString = 'M' . $timestamp . $keys;
        return $randString;
    }

    public function getAddFieldCalendar($value) {
        $value ['start_date'] = (!empty($value ['reserved_on']) && $value ['reserved_on'] != null) ? $value ['reserved_on_date_readable'] . " " . StaticFunctions::getFormattedDateTime($value ['reserved_on'], 'Y-m-d H:i:s', 'h:i A') : null;
        $value ['end_date'] = (!empty($value ['time_slot']) && $value ['time_slot'] != null) ? $value ['reserved_time_slot'] . " " . $value ['reserved_time'] : null;
        $value ['title'] = (!empty($value ['restaurant_name']) && $value ['restaurant_name'] != null) ? 'Reservation in ' . $value ['restaurant_name'] : null;
        $value ['description'] = (!empty($value ['restaurant_name']) && $value ['restaurant_name'] != null && !empty($value ['reserved_seats']) && $value ['reserved_seats'] != null && $value ['reserved_seats'] != null && !empty($value ['reserved_seats'])) ? 'Reservation in ' . $value ['restaurant_name'] . ' for ' . $value ['reserved_seats'] : null;
        $value ['location'] = (!empty($value ['address']) && $value ['address'] != null) ? $value ['address'] : null;
        return $value;
    }

    public function getNumberToString($number) {
        $data = (string) $number;
        $points = '';
        $userPointLen = strlen($data);
        for ($i = 0; $i < $userPointLen; $i ++) {
            $points .= '<b>' . $data [$i] . '</b>';
        }
        return isset($points) ? $points : '';
    }

    public function getPointStringFormat($mypoints = '') {
        $pointslength = (int) strlen($mypoints);
        switch ($pointslength) {

            case 1 :
                $mypoint = "0" . $mypoints;
                break;
            case 2 :
                $mypoint = $mypoints;
                break;
            case 3 :
                $mypoint = $mypoints;
                break;

            default :
                $mypoint = $mypoints;
                break;
        }
        $points = $this->getNumberToString($mypoint);
        return $points;
    }

    public static function timeleft($start, $end) {
        $sdate = strtotime($start);
        $edate = strtotime($end);

        $time = $sdate - $edate;
        $timeshift = 'expired';
        if ($sdate > $edate) {
            if ($time >= 0 && $time <= 59) {
                // Seconds
                $timeshift = $time . ' seconds left';
            } elseif ($time >= 60 && $time <= 3599) {
                // Minutes
                $pmin = $time / 60;
                $premin = explode('.', $pmin);
                $timeshift = $premin [0] . ' min left';
            } elseif ($time >= 3600 && $time <= 86399) {
                // Hours
                $phour = $time / 3600;
                $prehour = explode('.', $phour);
                $timeshift = $prehour [0] . ' hrs Left';
            } elseif ($time >= 86400) {
                // Days
                $pday = $time / 86400;
                $preday = explode('.', $pday);
                $timeshift = $preday [0] . ' days left';
            }
        }
        return $timeshift;
    }

    public function getShortDescription($orderedItems) {
        $strLength = 35;
        $descLength = strlen($orderedItems);

        if ($descLength > $strLength) {
            $shortDesc = substr($orderedItems, 0, $strLength) . ' ...';
        } else {
            $shortDesc = $orderedItems;
        }

        return $shortDesc;
    }

    public function timeLater($start, $end, $type = null) {
        $timeshift = 'expired';
        if ($type == 'ago') {
            $type = 'ago';
            $timeshift = date('M d, Y', strtotime($end));
        } else {
            $type = 'later';
        }
        $sdate = strtotime($start);
        $edate = strtotime($end);
        $time = $sdate - $edate;

        if ($sdate > $edate) {
            if ($time >= 0 && $time <= 59) {
                // Seconds
                $timeshift = $time . ' seconds' . " " . $type;
            } elseif ($time >= 60 && $time <= 3599) {
                // Minutes
                $pmin = $time / 60;
                $premin = explode('.', $pmin);
                $timeshift = $premin [0] . ' min' . " " . $type;
            } elseif ($time >= 3600 && $time <= 86399) {
                // Hours
                $phour = $time / 3600;
                $prehour = explode('.', $phour);
                $timeshift = $prehour [0] . ' hrs' . " " . $type;
            } elseif ($time >= 86400) {
                // Days
                $pday = $time / 86400;
                $preday = explode('.', $pday);
                $timeshift = $preday [0] . ' days' . " " . $type;
                if ($preday [0] > 7) {
                    $timeshift = StaticFunctions::getFormattedDateTime($start, 'Y-m-d H:i:s', 'M d, Y');
                }
            }
        }
        return $timeshift;
    }

    public function sendMails($data, $sender = array()) {
        $senderEmail = 'notifications@munchado.com';
        //as per bug id 38777
        if (!empty($sender) && isset($sender['first_name'])) {
            $senderName = isset($sender['first_name']) ? $sender['first_name'] : '';
        } else if (isset($data['loyality_code']) && $data['loyality_code'] === MUNCHADO_DINE_MORE_CODE) {
            $senderName = "Munch Ado";
        } else {
            $senderName = "Munch Ado";
        }

        // $c = StaticFunctions::getServiceLocator()->get('config');

        $recievers = array(
            $data ['receiver']
        );
        $template = "email-template/" . $data ['template'];
        $layout = (isset($data ['layout'])) ? $data ['layout'] : 'email-layout/default';

        $subject = $data ['subject'];
        $resquedata = array(
            'sender' => $senderEmail,
            'sendername' => $senderName,
            'variables' => $data ['variables'],
            'receivers' => $recievers,
            'template' => $template,
            'layout' => $layout,
            'subject' => $subject
        );
        StaticFunctions::resquePush($resquedata, 'SendEmail');
        // StaticFunctions::sendMail ( $sender, $sendername, $recievers, $template, $layout, $data ['variables'], $data ['subject'] );
    }

    // Make sure the key you pass exists in the database
    public function getAllocatedPoints($key) {
        $pointsModel = StaticFunctions::getServiceLocator()->get(PointSourceDetails::class);
        $points = $pointsModel->getPointsOnCssKey($key);
        return $points;
    }

    public function convertToDecimal($data) {
        $returnData = 0.00;
        if ($data == '' || $data == NULL || $data == 0 || $data == '0.00') {
            $returnData = '0.00';
        } else {
            $returnData = number_format((float) $data, 2, '.', '');
        }
        return $returnData;
    }

    public function getOffsetFromPage($page) {
        $offset = ($page - 1) * 50;
        return $offset;
    }

    public function getUserRejeectOrder() {
        $session = StaticFunctions::getUserSession();
        $userId = $session->getUserId();

        $locationData = $session->getUserDetail('selected_location');
        $stateCode = isset($locationData ['state_code']) ? $locationData ['state_code'] : 'CA';

        if (!$stateCode) {
            throw new \Exception("Invalid State Code. Please select City", 400);
        }

        // Get city Date Time Relatively
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $stateCode
        ));

        $date = $cityDateTime->format("Y-m-d H:i:s");
        $userOrderModel = new UserOrder ();
        $preOrderItemModel = new PreOrderItem ();
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');

        $orderStatus = isset($config ['constants'] ['order_status']) ? $config ['constants'] ['order_status'] : array();
        $status [] = $orderStatus [5];

        /**
         * Get individual order have latest activity
         */
        $rejeectOrder = isset($config ['constants'] ['order_type'] ['individual']) ? $config ['constants'] ['order_type'] ['individual'] : 'I';

        $individualData = $userOrderModel->userlastorder($userId, $date, $rejeectOrder, $status);
        $output = array();
        if (empty($individualData)) {
            $individualData = array();
        } else {
            foreach ($individualData as $individualData) {
                $archiveTime = "";
                $deliveryTime = "";
                $live = false;
                $archiveTime = $individualData ['archive_time'];
                $deliveryTime = $individualData ['delivery_time'];

                if (!empty($individualData ['archive_time']) || $individualData ['archive_time'] != NULL) {
                    if (strtotime($archiveTime) >= strtotime($date)) {
                        $live = true;
                    }
                } elseif (strtotime($deliveryTime) >= strtotime($date)) {
                    $live = true;
                }
                if ($live == true) {
                    $output [] = $individualData;
                }
            }
        }
        $record = array();
        if (isset($output) && !empty($output)) {

            foreach ($output as $key1 => $value) {
                $dateData [] = StaticFunctions::getFormattedDateTime($value ['delivery_time'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            }
            array_multisort($dateData, SORT_ASC, $output);
            $record = array_slice($output, 0, 1);
            $record = $record [0];
            $individualDataItem = $preOrderItemModel->getPreOrder(array(
                'columns' => array(
                    'item',
                    'quantity'
                ),
                'where' => array(
                    'pre_order_id' => $record ['pre_order_id'],
                    'user_id' => $userId
                )
            ));
            $individualItemData_new = $preOrderItemModel->getPreOrderItem($individualDataItem);
            $record ['order_description'] = $individualItemData_new;
            $orderTime = StaticFunctions::getFormattedDateTime($record ['created_at'], 'Y-m-d H:i:s', 'Y-m-d');
            $compOrderTime = StaticFunctions::getFormattedDateTime($record ['created_at'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
            $currentDate = StaticFunctions::getFormattedDateTime($date, 'Y-m-d H:i:s', 'Y-m-d');

            if ($orderTime == $currentDate) {
                $record ['order_time'] = $userOrderModel->dateToString($compOrderTime, $date);
            } else {
                $record ['order_time'] = StaticFunctions::getFormattedDateTime($record ['created_at'], 'Y-m-d H:i:s', 'M d, Y');
            }
            $record ['deliver_on'] = StaticFunctions::getFormattedDateTime($record ['delivery_time'], 'Y-m-d H:i:s', 'h:i A');
            $record ['order_status'] = 'rejected';
        }

        return $record;
    }

    public function findImageUrlNormal($image, $userId = null) {
        if ($image == '' || $image == NULL) {
            return WEB_URL . 'img/no_img.jpg';
        }

        if (preg_match('/http/', strtolower($image))) {
            return $image;
        }

        if (@getimagesize(APP_PUBLIC_PATH . USER_IMAGE_UPLOAD . "profile/" . $userId . DS . $image) !== false) {
            return WEB_URL . USER_IMAGE_UPLOAD . "profile" . DS . $userId . DS . $image;
        }

        return WEB_URL . 'img/no_img.jpg';
    }

    public function saveCardToStripeAndDatabase($cardDetails, $saveCard = false, $orderPass = false) {

        $cust_id = NULL;
        $card_number = NUll;
        $stripeModel = new MStripe ();
        $useCardModel = new UserCard ();
        $userId = StaticFunctions::getUserSession()->getUserId();

        $locationData = StaticFunctions::getUserSession()->getUserDetail('selected_location');
        $currentDate = strtotime($this->userCityTimeZone($locationData));
        $currentMonth = date("n", $currentDate);
        $currentYear = date("Y", $currentDate);

        $uDetails = ($userId > 0) ? $useCardModel->fetchUserCard($userId) : array();

        $card_number = array();

        if (!empty($uDetails)) {
            foreach ($uDetails as $key => $val) {
                $date = explode('/', $val['expired_on']);
                $cardValidate = 0;
                if ($currentYear < $date[1]) {
                    $cardValidate = 1;
                } elseif ($currentYear == $date[1]) {
                    if ($date[0] >= $currentMonth) {
                        $cardValidate = 1;
                    } else {
                        $cardValidate = 0;
                    }
                } else {
                    $cardValidate = 0;
                }
                if ($cardValidate == 1) {
                    $cust_id = $val['stripe_token_id'];
                    $card_number[] = $val['card_number'];
                }
            }
        }

        //Add to card in strip and get token and card detail	
        $fourDigitofCardNo = substr($cardDetails['number'], -4);
        if (in_array($fourDigitofCardNo, $card_number)) {
            $add_card_response = $stripeModel->addCard($cardDetails, $cust_id);
        } else {
            $cust_id = NULL;
            $add_card_response = $stripeModel->addCard($cardDetails, $cust_id);
        }

        if ($add_card_response ['status'] == 1) {

            if (in_array($add_card_response ['response'] ['last4'], $card_number)) {
                
            } elseif ($saveCard || $orderPass == 1) {
                $orderFunctions = new OrderFunctions();
                $useCardModel->user_id = $userId;
                $useCardModel->stripe_user_id = $add_card_response['response']['id'];
                $useCardModel->card_number = $add_card_response ['response'] ['last4'];
                $useCardModel->card_type = $add_card_response ['response'] ['type'];
                $useCardModel->name_on_card = $add_card_response ['response'] ['name'];
                $useCardModel->stripe_token_id = $add_card_response ['response'] ['customer'];
                $useCardModel->expired_on = $add_card_response ['response'] ['exp_month'] . "/" . $add_card_response ['response'] ['exp_year'];
                $useCardModel->zipcode = $add_card_response['response']['address_zip'];
                $useCardModel->encrypt_card_number = $orderFunctions->aesEncrypt($cardDetails['number'] . "-" . $cardDetails ['cvc']);
                $save_card_response = $useCardModel->addCard();
            }
        }

        if (isset($save_card_response)) {
            $add_card_response ['response']['card_inserted_id'] = $useCardModel->id;
            $add_card_response ['response']['status'] = 1;
            $add_card_response ['response'] ['stripe_token'] = $save_card_response ['stripe_token_id'];
            return $add_card_response ['response'];
        }

        return $add_card_response ['response'];
    }

    public function saveCardToStripeAndDatabaseMob($cardDetails) {

        $cust_id = NULL;
        $card_number = NUll;
        $stripeModel = new MStripe ();
        $useCardModel = new UserCard ();
        $userId = StaticFunctions::getUserSession()->getUserId();

        $locationData = StaticFunctions::getUserSession()->getUserDetail('selected_location');
        $currentDate = strtotime($this->userCityTimeZone($locationData));
        $currentMonth = date("n", $currentDate);
        $currentYear = date("Y", $currentDate);

        $uDetails = $useCardModel->fetchUserCard($userId);

        $card_number = array();

        if (!empty($uDetails)) {
            foreach ($uDetails as $key => $val) {
                $date = explode('/', $val['expired_on']);
                $cardValidate = 0;
                if ($currentYear < $date[1]) {
                    $cardValidate = 1;
                } elseif ($currentYear == $date[1]) {
                    if ($date[0] >= $currentMonth) {
                        $cardValidate = 1;
                    } else {
                        $cardValidate = 0;
                    }
                } else {
                    $cardValidate = 0;
                }
                if ($cardValidate == 1) {
                    $cust_id = $val['stripe_token_id'];
                    $card_number[] = $val['card_number'];
                }
            }
        }

        //Add to card in strip and get token and card detail	
        $fourDigitofCardNo = substr($cardDetails['number'], -4);
        if (in_array($fourDigitofCardNo, $card_number)) {
            $add_card_response = $stripeModel->addCard($cardDetails, $cust_id);
        } else {
            $cust_id = NULL;
            $add_card_response = $stripeModel->addCard($cardDetails, $cust_id);
        }

        if ($add_card_response ['status'] == 1) {

            if (in_array($add_card_response ['response'] ['last4'], $card_number)) {
                throw new \Exception("Card detail already exist", 400);
            } else {
                $orderFunctions = new OrderFunctions();
                $useCardModel->user_id = $userId;
                $useCardModel->stripe_user_id = $add_card_response['response']['id'];
                $useCardModel->card_number = $add_card_response ['response'] ['last4'];
                $useCardModel->card_type = $add_card_response ['response'] ['type'];
                $useCardModel->name_on_card = $add_card_response ['response'] ['name'];
                $useCardModel->stripe_token_id = $add_card_response ['response'] ['customer'];
                $useCardModel->expired_on = $add_card_response ['response'] ['exp_month'] . "/" . $add_card_response ['response'] ['exp_year'];
                $useCardModel->zipcode = $add_card_response['response']['address_zip'];
                $useCardModel->encrypt_card_number = $orderFunctions->aesEncrypt($cardDetails['number'] . "-" . $cardDetails ['cvc']);
                $save_card_response = $useCardModel->addCard();
            }
        }

        if (isset($save_card_response)) {
            $add_card_response ['response']['card_inserted_id'] = $useCardModel->id;
            $add_card_response ['response']['status'] = 1;
            $add_card_response ['response'] ['stripe_token'] = $save_card_response ['stripe_token_id'];
            return $add_card_response ['response'];
        }

        return $add_card_response ['response'];
    }

    public function saveCardDatabase($cardDetails) {
        $userId = StaticFunctions::getUserSession()->getUserId();
        $orderFunctions = new OrderFunctions();
        $useCardModel = new UserCard ();
        $fourDigitofCardNo = substr($cardDetails['number'], -4);
        $useCardModel->user_id = $userId;
        $useCardModel->stripe_user_id = "";
        $useCardModel->card_number = $fourDigitofCardNo;
        $useCardModel->card_type = '';
        $useCardModel->name_on_card = $cardDetails['name'];
        $useCardModel->stripe_token_id = "";
        $useCardModel->expired_on = $cardDetails ['exp_month'] . "/" . $cardDetails ['exp_year'];
        $useCardModel->zipcode = $cardDetails['address_zip'];
        $useCardModel->encrypt_card_number = $orderFunctions->aesEncrypt($cardDetails['number'] . "-" . $cardDetails ['cvc']);
        $useCardModel->addCard();
    }

    public function givePoints($points, $userId, $message = null, $refId = null, $takeThisUserId = false) {
        $userIdFromSession = isset($userId) ? $userId : StaticFunctions::getUserSession()->getUserId();
        $locationData = StaticFunctions::getUserSession()->getUserDetail('selected_location', array());
        $currentDate = $this->userCityTimeZone($locationData);
        if ($this->restaurantId) {
            $currentDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $this->restaurantId
                    ))->format(StaticFunctions::MYSQL_DATE_FORMAT);
        }
        if ($userIdFromSession && !$takeThisUserId) {
            $userId = $userIdFromSession;
        }
        $userPointsModel = StaticFunctions::getServiceLocator()->get(UserPoint::class);

        if (isset($points ['type']) && $points ['type'] == "normalRegister") {
            $data = array(
                'user_id' => $userId,
                'point_source' => $points ['id'],
                'points' => $points ['points'],
                'created_at' => $currentDate,
                'status' => 1,
                'points_descriptions' => $message,
                'ref_id' => $refId,
            );
        } else {
            $data = array(
                'user_id' => $userId,
                'point_source' => $points ['id'],
                'points' => $points ['points'],
                'created_at' => $currentDate,
                'status' => 1,
                'points_descriptions' => $message,
                'ref_id' => $refId,
                'restaurant_id' => ($this->restaurantId) ? $this->restaurantId : 0
            );
        }

        $userPointsModel->createPointDetail($data);
        $userModel = StaticFunctions::getServiceLocator()->get(User::class);
        $currentPoints = $userModel->countUserPoints($userId);
        if (!empty($currentPoints)) {
            $totalPoints = $currentPoints [0] ['points'] + $points ['points'];
        } else {
            $totalPoints = $points ['points'];
        }
        $userModel->updateUserPoint($userId, $totalPoints);
    }

    public function sendOrderMail($data, $status, $userId, $restaurant_id) {
        $template = '';
        $subject = '';
        $restaurantAccount = new RestaurantAccounts ();
        $variables = $data;

        #Mail to restaurnt#
        if ($data ['type'] == 'Delivery') {
            $template = 'place-order-delivery';
            $subject = "You've Got a New Delivery Order from Munch Ado!";
        } else {
            $template = 'place-order-takeout';
            $subject = "You've Got a New Takeout Order from Munch Ado!";
        }

        //$sendMailToRestaurant = $restaurantAccount->checkRestaurantForMail($restaurant_id, 'orderconfirm');

        $restaurantMail = $restaurantAccount->getRestaurantAccountDetail(
                array('columns' => array('email'), 'where' => array('restaurant_id' => $restaurant_id))
        );

        $recievers = array($restaurantMail ['email']);
        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template
        );

        $this->sendMailsToRestaurant($emailData);
        return true;
    }

    public function sendRegistrationEmail($data) {
        $recievers = array(
            $data ['recievers']
        );
        $template = $data ['template'];
        $layout = $data ['layout'];
        $variables = $data ['variables'];
        $subject = (isset($data['subject']) && !empty($data['subject'])) ? $data['subject'] : 'Welcome to Munch Ado!';
        // $emailData = array('sender'=> $sender, 'sendername'=>$sendername,'receivers'=>$recievers,'template'=>$template,'layout'=>$layout,'variables'=>$variables,'subject'=>$subject);
        // StaticFunctions::resquePush($emailData);
        /* TODO Impliment resque */
        // #################
        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout
        );

        // #################
        $this->sendMails($emailData);
    }

    public function pointAddedInUserAccount($userId, $pointSourceId, $refId = NULL, $msg = NULL) {
        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $this->userCityTimeZone($locationData);
        $pointSourceModel = new PointSourceDetails ();
        $pointModel = new UserPoint ();
        $userModel = new User ();
        $userData = $userModel->getUserDetail(array(
            'column' => array(
                'points'
            ),
            'where' => array(
                'id' => $userId
            )
        ));

        $userPoints = $userData ['points'];
        $userPoints = !empty($userPoints) ? $userPoints : 0;
        $points = $pointSourceModel->getPointSourceDetail(array(
            'column' => array(
                'points'
            ),
            'where' => array(
                'id' => $pointSourceId
            )
        ));

        $userModel->id = $userId;
        $userTotalPoints = (int) $userPoints + (int) $points ['points'];
        $userModel->update(array(
            'points' => $userTotalPoints
        ));
        $pointArray = array(
            'user_id' => $userId,
            'point_source' => $pointSourceId,
            'points' => $points ['points'],
            'created_at' => $currentDate,
            'status' => 1,
            'points_descriptions' => $msg,
            'ref_id' => $refId
        );

        $response = $pointModel->createPointDetail($pointArray);

        return true;
    }

    public function getPointStringFormatNew($mypoints = '') {
        $pointslength = (int) strlen($mypoints);
        switch ($pointslength) {

            case 1 :
                $mypoint = "0" . $mypoints;
                break;
            case 2 :
                $mypoint = $mypoints;
                break;
            case 3 :
                $mypoint = $mypoints;
                break;

            default :
                $mypoint = $mypoints;
                break;
        }
        // $points = $this->getNumberToString ( $mypoint );
        return $mypoint;
    }

    public function manipulateDeliveryTime($deliveryTime, $restaurant_id) {
        $currDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'restaurant_id' => $restaurant_id
        ));

        $currentDateTime = $currDateTime->format('Y-m-d H:i:s');
        $currentDate = $currDateTime->format('Y-m-d');
        if (!empty($deliveryTime)) {
            $dateArr = explode('#', $deliveryTime);

            if ($deliveryTime == 'TODAY#ASAP') {
                $timeInterval = $this->roundToNearestInterval(strtotime($currentDateTime));
                $deliveryTime = date('Y-m-d H:i:s', $timeInterval);
                // $deliveryTime=StaticFunctions::getFormattedDateTime ( $time, 'H:i:s', 'Y-m-d H:i:s' );
            } else if ($dateArr [0] == 'TOMORROW') {
                $currDateTime->add(new \DateInterval('P1D'));
                $nextDay = $currDateTime->format('Y-m-d');
                $deliveryTime = $nextDay . ' ' . $dateArr [1];
                // $deliveryTime=StaticFunctions::getFormattedDateTime ( $dateInterval, 'Y-m-d H:i:s', 'Y-m-d H:i:s' );
            } else {
                $delivery_date_month = $dateArr [0];
                $time = explode(' ', $dateArr [1]);

                if (strtoupper($delivery_date_month) == 'TODAY') {
                    $deliveryTime = date('Y-m-d H:i:s', strtotime($currentDate . ' ' . $time [0]));
                } else {

                    if ($dateArr [1] == 'ASAP') {
                        $deliveryTime = date('Y-m-d H:i:s', strtotime(trim($dateArr [1])));
                    } else {
                        $delivery_date_time = $delivery_date_month . " " . $time [0];
                        $deliveryTime = date('Y-m-d H:i:s', strtotime($delivery_date_time));
                    }
                }
            }
        } else {
            $deliveryTime = $currentDateTime;
        }

        return $deliveryTime;
    }

    public function roundToNearestInterval($timestamp) {
        $timestamp += 60 * 30;
        list ( $m, $d, $y, $h, $i, $s ) = explode(' ', date('m d Y H i s', $timestamp));
        if ($s != 0)
            $s = 0;
        // print $i;
        if ($i <= 30) {
            $i = 30;
        } else if ($i < 60) {
            $i = 0;
            $h ++;
        }
        return mktime($h, $i, $s, $m, $d, $y);
    }

    public function to_utf8($in) {
        if (is_array($in)) {
            foreach ($in as $key => $value) {
                $out [$this->to_utf8($key)] = $this->to_utf8($value);
            }
        } elseif (is_string($in)) {
            if (mb_detect_encoding($in) != "UTF-8")
                return utf8_encode($in);
            else
                return $in;
        } else {
            return $in;
        }
        return $out;
    }

    public function getPreOrderItem($orderDetails) {
        if (empty($orderDetails)) {
            $orderDescription = "No Item";
        } else {
            $orderDescription = '';
            foreach ($orderDetails as $key => $details) {
                $orderDescription .= $details ['quantity'] . ' ' . $details ['item'] . ',';
            }
            if (!empty($orderDescription)) {
                $orderDescription = substr($orderDescription, 0, - 1);
            }
        }

        return empty($orderDescription) ? '' : $orderDescription;
    }

    public function cronOrder() {
        $userOrderModel = new UserOrder ();
    }

    public function makeFriends($email, $userId) {
        $userInvitation = new UserFriendsInvitation ();
        $userInvitation->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'user_id'
            ),
            'where' => array(
                'email' => $email
            )
        );
        $friendsToMake = $userInvitation->find($options)->toArray();
        if (!empty($friendsToMake)) {
            foreach ($friendsToMake as $friends) {
                $userFriends = new UserFriends ();
                $dataArray = array(
                    'user_id' => $userId,
                    'friend_id' => $friends ['user_id'],
                    'created_on' => StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT),
                    'status' => 1
                );
                $userFriends->createFriends($dataArray, $friends ['user_id']);
                $dataArray = array(
                    'user_id' => $friends ['user_id'],
                    'friend_id' => $userId,
                    'created_on' => StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT),
                    'status' => 1
                );
                $userFriends->createFriends($dataArray, false);
            }
        }
    }

    public function filterArraywithAnd($arr) {
        if (!empty($arr)) {

            $data = "";

            $arr = explode(',', $arr);
            $count = count($arr);
            $i = 1;
            foreach ($arr as $key => $val) {
                if ($i == $count - 1)
                    $data .= $val . ' and ';
                else
                    $data .= $val . ', ';
                $i ++;
            }
            $data = substr($data, 0, - 2);
            return $data;
        }
    }

    public function getCounts($response) {
        $lovedIt = 0;
        $wantIt = 0;
        $triedIt = 0;
        foreach ($response as $single) {
            if ($single ['loved_it']) {
                $lovedIt ++;
            }
            if ($single ['want_it']) {
                $wantIt ++;
            }
            if ($single ['tried_it']) {
                $triedIt ++;
            }
        }
        return array(
            'loved_it_count' => $lovedIt,
            'want_it_count' => $wantIt,
            'tried_it_count' => $triedIt
        );
    }

    public function getRestaurantBookmarkCounts($response) {
        $lovedIt = 0;
        $beenThere = 0;
        $craveIt = 0;

        foreach ($response as $single) {
            if ($single ['loved_it']) {
                $lovedIt ++;
            }
            if ($single ['been_there']) {
                $beenThere ++;
            }
            if ($single ['crave_it']) {
                $craveIt ++;
            }
        }
        return array(
            'loved_it_count' => $lovedIt,
            'been_there_count' => $beenThere,
            'crave_it_count' => $craveIt
        );
    }

    public function sendMailsToRestaurant($data, $sender = false) {
        $sender = 'wecare@munchado.com';
        if ($sender) {
            $sendername = $sender;
        } else {
            $sendername = "Munch Ado";
        }
        // $c = StaticFunctions::getServiceLocator()->get('config');

        $recievers = array(
            $data ['receiver']
        );
        $template = "email-template/" . $data ['template'];
        $layout = (isset($data ['layout'])) ? $data ['layout'] : 'email-layout/default';

        $subject = $data ['subject'];
        $resquedata = array(
            'sender' => $sender,
            'sendername' => $sendername,
            'variables' => $data ['variables'],
            'receivers' => $recievers,
            'template' => $template,
            'layout' => $layout,
            'subject' => $subject
        );

        StaticFunctions::resquePush($resquedata, 'SendEmail');
        // StaticFunctions::sendMail ( $sender, $sendername, $recievers, $template, $layout, $data ['variables'], $data ['subject'] );
    }

    public function createSettings($userId, $ns = false) {
        $userSettingsModel = StaticFunctions::getServiceLocator()->get(UserSetting::class);
        $userSettingsModel->create1($userId, $ns);
        return true;
    }

    public function checkIfEmailExists($email) {
        $user = new User ();
        $user->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'count' => new \Zend\Db\Sql\Expression('COUNT(*)'),
                'id',
                'first_name'
            ),
            'where' => array(
                'email' => $email
            )
        );
        $userDetails = $user->find($options)->current()->getArrayCopy();
        if ($userDetails ['count']) {
            return $userDetails;
        }
        return false;
    }

    public function invitationAccepted($id, $registeredWithDifferentEmail = false, $newUser = false, $inteeEmail = false) {
        $invitationModel = new UserFriendsInvitation ();
        $invitationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        if (isset($inteeEmail) && $inteeEmail != '') {
            $options = array(
                'columns' => array(
                    'id',
                    'user_id',
                    'email'
                ),
                'where' => array(
                    'status' => 1,
                    'user_id' => $id,
                    'email' => $inteeEmail
                )
            );
        } else {
            $options = array(
                'columns' => array(
                    'id',
                    'user_id',
                    'email'
                ),
                'where' => array(
                    'status' => 1,
                    'id' => $id
                )
            );
        }
        $invitationDetails = $invitationModel->find($options)->toArray();
        // check if email user has account
        if (!$invitationDetails) {
            throw new \Exception('Invitation expired or does not exist', 400);
        } else {
            $id = isset($invitationDetails[0]['id']) ? $invitationDetails[0]['id'] : $id;
        }
        $emailRegistered = ($registeredWithDifferentEmail) ? $registeredWithDifferentEmail : $invitationDetails [0] ['email'];

        $userExists = $this->checkIfEmailExists($emailRegistered);
        $cityModel = new \Home\Model\City(); //18848
        $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
        $cityDetails = $cityModel->cityDetails($cityId);
        $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                    'state_code' => $cityDetails [0] ['state_code']
        ));

        $currentDateTime = $cityDateTime->format('Y-m-d H:i:s');
        // if it has account
        if ($userExists) {
            $inviterId = $invitationDetails [0] ['user_id'];
            $inviteeId = $userExists['id'];
            if ($inviteeId == $inviterId) {
                return true;
            }
            $userFriends = new UserFriends ();
            $dataArray = array(
                'user_id' => $inviterId,
                'friend_id' => $inviteeId,
                'invitation_id' => $id,
                'created_on' => $currentDateTime,
                'status' => 1
            );
            $pointsToGive = $inviterId;
            if (!$newUser) {
                $pointsToGive = false;
            }
            $userFriends->createFriends($dataArray, $pointsToGive);
            $dataArray = array(
                'user_id' => $inviteeId,
                'friend_id' => $inviterId,
                'invitation_id' => $id,
                'created_on' => $currentDateTime,
                'status' => 1
            );
            $userFriends->createFriends($dataArray, false);
            $data = array('invitation_status' => '1');
            $predicate = array('id' => $id);
            $invitationDetails = $invitationModel->abstractUpdate($data, $predicate);
            //madeFriend
            ####################### Assign points user for registration #######################
//            $points = $this->getAllocatedPoints('madeFriend');        
//            $message = 'Friend Accept Invitation! You\'ll need points to have the most fun, here take 15. Hoard them wisely.';
//            $this->givePoints($points, $inviteeId, $message); 
            #############################################################################
            ######### pubnub push ###########            
            $currentDate = $currentDateTime;
            $notificationMsg = ucfirst($userExists['first_name']) . " accepted your friend request. Soon you two will be finishing each other's sandwich orders.";
            $channel = "mymunchado_" . $inviterId;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $inviteeId,
                "friend_iden_Id" => $inviterId,
                "type" => 'invite_friends',
                "restaurantId" => '0',
                'curDate' => $currentDate,
                'username' => ucfirst($userExists['first_name'])
            );
            $notificationJsonArray = array('user_id' => $inviteeId, 'username' => ucfirst($userExists['first_name']));
            //$userNotificationModel = new UserNotification();
            //$userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
            //StaticFunctions::pubnubPushNotification($notificationArray);
            //notification send to invitee
            $findUserModel = new User();
            $inviteeName = $findUserModel->getName($inviterId);
            $notificationMsg = "You and " . ucfirst($inviteeName) . " are now friends on Munch Ado! Just think of all the food you'll eat together.";
            $channel = "mymunchado_" . $inviteeId;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $inviterId,
                "friend_iden_Id" => $inviteeId,
                "type" => 'invite_friends',
                "restaurantId" => '0',
                'curDate' => $currentDate,
                'username' => ucfirst($inviteeName)
            );
            $notificationJsonArray = array('user_id' => $inviterId, 'username' => ucfirst($inviteeName));
            //$userNotificationModel = new UserNotification();
            //$userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
            //StaticFunctions::pubnubPushNotification($notificationArray);

            $feed = array(
                'user_id' => $inviterId,
                "friend_id" => $inviteeId,
                'inviter' => ucfirst($inviteeName),
                'user' => ucfirst($userExists['first_name'])
            );
            $replacementData = array('inviter' => ucfirst($inviteeName));
            $otherReplacementData = array('user' => ucfirst($userExists['first_name']), 'inviter' => ucfirst($inviteeName));

            $commonFunction = new \MCommons\CommonFunctions();
            $activityFeed = $commonFunction->addActivityFeed($feed, 54, $replacementData, $otherReplacementData);

            #################################       
            return true;
        } else {
            return false;
        }
    }

    public function addUserAddress($data) {
        $addressModel = new UserAddress ();
        $response = $addressModel->insert($data);
        return $response;
    }

    public function checkRestaurantStatus($restaurant_id, $deleveryTime) {
        if (!empty($restaurant_id) && !empty($deleveryTime)) {
            $endDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT), 'Y-m-d H:i:s');
            $deleveryTimes = StaticFunctions::getAbsoluteCityDateTime(array(
                        'restaurant_id' => $restaurant_id
                            ), $deleveryTime, 'Y-m-d H:i:s');
            if ($deleveryTimes < $endDate) {
                $restaurant = new Restaurant ();
                $restaurant->getDbTable()->setArrayObjectPrototype('ArrayObject');
                $options = array(
                    'columns' => array(
                        'closed',
                        'inactive'
                    ),
                    'where' => array(
                        'id' => $restaurant_id
                    )
                );
                $restaurantData = $restaurant->find($options)->toArray();
                if ($restaurantData [0] ['closed'] == 0 && $restaurantData [0] ['inactive'] == 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    public function reservationInvitationAccepted($id, $registeredWithDifferentEmail = false, $newUser = false) {
        $reservationInvitationModel = new UserInvitation ();
        $reservationModel = new UserReservation();
        $reservationInvitationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'user_id',
                'friend_email',
                'to_id',
                'restaurant_id',
                'user_type',
                'reservation_id'
            ),
            'where' => array(
                'msg_status' => 0,
                'id' => $id
            )
        );
        $reservationInvitationDetails = $reservationInvitationModel->find($options)->toArray();
        // check if email user has account
        if (!$reservationInvitationDetails) {
            return false;
            //throw new \Exception ( 'Invitation expired or does not exist' );
        }

        if ($reservationInvitationDetails [0] ['user_id'] == $reservationInvitationDetails [0] ['to_id']) {
            return true;
        }
        $reservationOptions = array(
            'columns' => array(
                'party_size',
                'time_slot', 'status'
            ),
            'where' => array(
                'id' => $reservationInvitationDetails [0] ['reservation_id']
            )
        );
        $getReservationDetails = $reservationModel->getUserReservation($reservationOptions);

        $reservationSize = '';
        $reservationDate = '';
        $reservationTime = '';
        if (count($getReservationDetails) > 0) {
            $reservationSize = $getReservationDetails[0]['party_size'];
            $reservationTimeSlot = strtotime($getReservationDetails[0]['time_slot']);
            $reservationDate = date('M d Y', $reservationTimeSlot);
            $reservationTime = date('H:i a', $reservationTimeSlot);
        }
        $acceptedReservationInvitation = $reservationInvitationModel->updateReservationInvitaion($id);

        if ($acceptedReservationInvitation) {

            // give point to inviter
            $key = 'acceptReservation';
            $points = $this->getAllocatedPoints($key);
            $this->givePoints($points, $reservationInvitationDetails [0] ['user_id'], $message = "Friend joins your reservation");

            // give notification to inviter--push pubnub
            // pubnub notification
            $userNotificationModel = new UserNotification ();
            $commonFunctiion = new \MCommons\CommonFunctions();
            $userModel = new User ();
            $restaurantModel = new Restaurant ();
            $userDetails = $userModel->getUserDetail(array(
                'where' => array(
                    'email' => $reservationInvitationDetails [0] ['friend_email']
                )
            ));
            if ($userDetails) {
                $userName = (isset($userDetails['last_name']) && !empty($userDetails['last_name'])) ? $userDetails['first_name'] . " " . $userDetails['last_name'] : $userDetails['first_name'];
            } else {
                $userDetailsArr = explode("@", $reservationInvitationDetails [0] ['friend_email']);
                $userName = $userDetailsArr [0];
            }

            // get restaurant name
            $restaurant = $restaurantModel->findRestaurant(array(
                'column' => array(
                    'restaurant_name'
                ),
                'where' => array(
                    'id' => $reservationInvitationDetails [0] ['restaurant_id']
                )
            ));

            $currentDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $reservationInvitationDetails [0] ['restaurant_id']
            ));

            $inviterDetails = $userModel->getUserDetail(array(
                'where' => array(
                    'email' => $reservationInvitationDetails [0] ['user_id']
                )
            ));

            $currentDate = $currentDate->format('Y-m-d H:i:s');
            $notificationMsg = ucfirst($userName) . ' joined your reservation at ' . $restaurant->restaurant_name . ' in a shared quest for food greatness.';
            $channel = "mymunchado_" . $reservationInvitationDetails [0] ['user_id'];
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $reservationInvitationDetails [0] ['to_id'],
                "friend_iden_Id" => $reservationInvitationDetails [0] ['user_id'],
                "type" => 'reservation',
                "restaurantId" => $reservationInvitationDetails [0] ['restaurant_id'],
                'curDate' => $currentDate,
                'username' => ucfirst($userName),
                'restaurant_name' => $restaurant->restaurant_name,
                'reservation_id' => $reservationInvitationDetails [0] ['reservation_id'],
                'reservation_status' => $getReservationDetails[0]['status']
            );
            $notificationJsonArray = array('reservation_id' => $reservationInvitationDetails [0] ['reservation_id'],
                'reservation_status' => $getReservationDetails[0]['status'], 'user_id' => $reservationInvitationDetails [0] ['to_id'], 'username' => ucfirst($userName), 'restaurant_id' => $reservationInvitationDetails [0] ['restaurant_id'], 'restaurant_name' => $restaurant->restaurant_name);
            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
            $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);
            $replacementData = array('friend_name' => ucfirst($userName), 'restaurant_name' => $restaurant->restaurant_name);
            $inviterName = (isset($inviterDetails['last_name']) && !empty($inviterDetails['last_name'])) ? $inviterDetails['first_name'] . " " . $inviterDetails['last_name'] : $inviterDetails['first_name'];
            $otherReplacementData = array('friend_name' => ucfirst($userName), 'user_name' => ucfirst($inviterName), 'restaurant_name' => $restaurant->restaurant_name);
            $feed = array(
                'restaurant_id' => $reservationInvitationDetails [0] ['restaurant_id'],
                'restaurant_name' => $restaurant->restaurant_name,
                'user_name' => ucfirst($userName),
                'img' => [],
                'reservation_time' => $reservationTime,
                'reservation_date' => $reservationDate,
                'event_date_time' => $reservationDate,
                'no_of_people' => $reservationSize,
                'friend_id' => $reservationInvitationDetails [0] ['to_id'],
                'user_id' => $reservationInvitationDetails [0] ['user_id']
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 6, $replacementData, $otherReplacementData);

            return true;
        } else {
            return false;
        }
    }

    public function sendMailsToServiceProvider($data) {
        $sender = 'grow@munchado.biz';
        $sendername = "Munch Ado";
        // $c = StaticFunctions::getServiceLocator()->get('config');

        $recievers = $data ['receiver'];

        $template = "email-template/" . $data ['template'];
        $layout = 'email-layout/default';

        $subject = $data ['subject'];
        $mailToServiceProvider = StaticFunctions::sendMailToServiceProvider($sender, $sendername, $recievers, $template, $layout, $data ['variables'], $data ['subject'], $data['receiverCc'], $data['attachment']);
        return $mailToServiceProvider;
    }

    public function getDeliveryPickupTime($deliveryDate, $deliveryTime = NULL, $orderDate, $orderTime = NULL, $status) {
        $pickuptime = false;
        if ($deliveryTime && $orderTime) {

            if ($status == "ordered") {
                $orderDateTime = $orderDate . " " . $orderTime;
                $deliveryDateTime = $deliveryDate . " " . $deliveryTime;
                ####################################################

                $deliveryDateTimeObj = new \DateTime($deliveryDateTime);
                $orderedDateTimeObj = new \DateTime($orderDateTime);
                $dateDefference = $deliveryDateTimeObj->diff($orderedDateTimeObj);
                $differenceHourMin = $dateDefference->format("%H:%I");
                $differenceHourMinArr = explode(":", $differenceHourMin);

                if ($differenceHourMinArr[0] != "00") {
                    $min = ($differenceHourMinArr[0] * 60) + $differenceHourMinArr[1];
                } else {
                    $min = $differenceHourMinArr[1];
                }

                if ($min > 45) {
                    $deliveryTimeStamp = strtotime($deliveryDateTime);
                    $pickuptime = date("m/d/Y H:i", strtotime(PRE_ORDER_PICKUP_TIME, $deliveryTimeStamp));
                } elseif ($min <= 45) {
                    $orderTimeStamp = strtotime($orderDateTime);
                    $pickuptime = date("m/d/Y H:i", strtotime(CURRENT_ORDER_PICKUP_TIME, $orderTimeStamp));
                }

                ####################################################
            } else {
                $deliveryDateTime = $deliveryDate . " " . $deliveryTime;
                $deliveryTimeStamp = strtotime($deliveryDateTime);
                $pickuptime = date("m/d/Y H:i", strtotime(PRE_ORDER_PICKUP_TIME, $deliveryTimeStamp));
            }
        }
        return $pickuptime;
    }

    public function createTextFileOfOrderForServiceprovider($data = NULL, $recieptNo) {
        $filePath = array();

        if ($data) {
            $filePath = SAMPLE_TEXT_FILE . "/" . $recieptNo . "_orderDetail.txt";
            $newFile = fopen($filePath, 'w+');
            fwrite($newFile, $data);
            fclose($newFile);
            chmod($filePath, 0777);
            $filePath = array('filepath' => $filePath, 'filename' => $recieptNo . "_orderDetail.txt");
        }
        return $filePath;
    }

    public function preOrderReservationMail($data, $preOrderReservation, $userId = '', $orderPass) {
        $variables = array();
        $orderFunctions = new OrderFunctions ();
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $restaurantId = $data ['order_details'] ['restaurant_id'];
        //$variables['order_pass_through']=$orderPass;
        $variables['restaurant_name'] = $data['reservation_details']['restaurant_name'];
        $variables['recieptNo'] = $preOrderReservation['reservation']['receipt_no'];
        $variables['reserved_seats'] = isset($data['reservation_details']['reserved_seats']) ? $data['reservation_details']['reserved_seats'] : '';

        $timeslot [0] = $data ['reservation_details']['date'];
        $timeslot [1] = $data ['reservation_details']['time'];
        $variables['order_pass_through'] = $orderPass;
        $variables['reservationDate'] = StaticFunctions::getFormattedDateTime($timeslot [0], 'Y-m-d', 'D, M d, Y');
        $variables['reservationtime'] = StaticFunctions::getFormattedDateTime($timeslot [1], 'H:i', 'h:i A');
        $variables['orderType'] = "Pre-paid reservation";
        $variables['reservationTime'] = StaticFunctions::getFormattedDateTime($timeslot [0], 'Y-m-d', 'D, M d, Y') . " " . StaticFunctions::getFormattedDateTime($timeslot [1], 'H:i', 'h:i A');
        $variables['specialInstructions'] = (!empty($data['order_details']['special_instruction'])) ? implode(",", $data['order_details']['special_instruction']) : "";
        $variables['order_details'] = $data['order_details']['items'];
        $variables['subtotal'] = $preOrderReservation['order']['subTotal'];
        $variables['dealDiscount'] = $preOrderReservation['order']['dealDiscount'];
        $variables['promocodeDiscount'] = $preOrderReservation['order']['promocodeDiscount'];
        $variables['tax'] = $preOrderReservation['order']['tax'];
        $variables['tipAmount'] = $preOrderReservation['order']['tipAmount'];
        $variables['total'] = $preOrderReservation['order']['finalTotal'];
        if ($preOrderReservation['order']['finalTotal'] > APPLIED_FINAL_TOTAL) {
            $variables['cardType'] = $data['card_details']['card_type'];
            $variables['cardNo'] = isset($data['card_details']['card_number']) ? $data['card_details']['card_number'] : substr($data['card_details']['card_no'], -4);
            $variables['expiredOn'] = $data['card_details']['expiry_month'] . "/" . $data['card_details']['expiry_year'];
        } else {
            $variables['cardType'] = "";
            $variables['cardNo'] = "";
            $variables['expiredOn'] = "";
        }
        $variables['username'] = $data['user_details']['fname'];
        $orderFunctions->calculatePrice($data ['order_details']);
        $status = $orderFunctions->getOrderStatus($data ['order_details'] ['delivery_date'], $data ['order_details'] ['delivery_time'], $restaurantId);
        $variables['orderData'] = $orderFunctions->makeOrderForMail($orderFunctions->itemDetails, $data['order_details']['restaurant_id'], $status, $preOrderReservation['order']['subTotal']);
        $variables['web_url'] = PROTOCOL . $config ['constants'] ['web_url'];
        $userModel = new User ();

        $restaurantAccount = new RestaurantAccounts ();

        //Mail to restaurant
        $template = 'New_Pre-Paid_Reservation';
        $subject = 'New Pre-Paid Reservation!';

        $sendMailToRestaurant = $restaurantAccount->checkRestaurantForMail($restaurantId, 'orderconfirm');
        $restaurantMail = $restaurantAccount->getRestaurantAccountDetail(array(
            'columns' => array(
                'email'
            ),
            'where' => array(
                'restaurant_id' => $restaurantId
            )
        ));
        if ($sendMailToRestaurant == true || $sendMailToRestaurant == 1) {
            $recievers = array(
                $restaurantMail ['email']
            );

            $emailData = array(
                'receiver' => $recievers,
                'variables' => $variables,
                'subject' => $subject,
                'template' => $template
            );
        }

        ### Email to user ###
        $userRecievers = array(
            $data ['user_details']['email']
        );
        $sendMail = false;
        if (!empty($userId)) {
            $sendMail = $userModel->checkUserForMail($userId, 'reservation');
        }
//            $userSubject = "It's a date! And time! And food!";
//            $userTemplate = 'Its_a_date_And_time_And_food';
        $userSubject = "Your Pre-Ordered Pre-Reservation, Pre-Approved";
        $userTemplate = 'Pre-Ordered_Pre-Reservation_Pre-Approved';

        if ($sendMail == true) {
            $emailData = array(
                'receiver' => $userRecievers,
                'variables' => $variables,
                'subject' => $userSubject,
                'template' => $userTemplate,
                'layout' => 'email-layout/default_new'
            );
            //$this->sendMails($emailData);
            $this->sendMailsToRestaurant($emailData);
        }
    }

    public function reservationInvitationDecline($id, $registeredWithDifferentEmail = false, $newUser = false) {
        $reservationInvitationModel = new UserInvitation ();
        $reservationInvitationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'user_id',
                'friend_email',
                'to_id',
                'restaurant_id',
                'user_type'
            ),
            'where' => array(
                'msg_status' => 0,
                'id' => $id
            )
        );
        $reservationInvitationDetails = $reservationInvitationModel->find($options)->toArray();
        // check if email user has account
        if (!$reservationInvitationDetails) {
            return false;
            //throw new \Exception ( 'Invitation expired or does not exist' );
        }

        if ($reservationInvitationDetails [0] ['user_id'] == $reservationInvitationDetails [0] ['to_id']) {
            return true;
        }

        $decilineReservationInvitation = $reservationInvitationModel->declineReservationInvitaion($id);

        if ($decilineReservationInvitation) {

            // Give notification to inviter--push pubnub
            // pubnub notification
            $userNotificationModel = new UserNotification ();
            $commonFunctiion = new \MCommons\CommonFunctions();
            $userModel = new User ();
            $restaurantModel = new Restaurant ();
            $userDetails = $userModel->getUserDetail(array(
                'where' => array(
                    'email' => $reservationInvitationDetails [0] ['friend_email']
                )
            ));
            if ($userDetails) {
                $userName = (isset($userDetails['last_name']) && !empty($userDetails['last_name'])) ? $userDetails['first_name'] . " " . $userDetails['last_name'] : $userDetails['first_name'];
            } else {
                $userDetailsArr = explode("@", $reservationInvitationDetails [0] ['friend_email']);
                $userName = $userDetailsArr [0];
            }

            // get restaurant name
            $restaurant = $restaurantModel->findRestaurant(array(
                'column' => array(
                    'restaurant_name'
                ),
                'where' => array(
                    'id' => $reservationInvitationDetails [0] ['restaurant_id']
                )
            ));

            $currentDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $reservationInvitationDetails [0] ['restaurant_id']
            ));
            $currentDate = $currentDate->format('Y-m-d H:i:s');
            $notificationMsg = ucfirst($userName) . ' RSVDecline “A-doy” to your reservation at ' . $restaurant->restaurant_name;
            $channel = "mymunchado_" . $reservationInvitationDetails [0] ['user_id'];
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $reservationInvitationDetails [0] ['to_id'],
                "friend_iden_Id" => $reservationInvitationDetails [0] ['user_id'],
                "type" => 'reservation',
                "restaurantId" => $reservationInvitationDetails [0] ['restaurant_id'],
                'curDate' => $currentDate,
                'username' => ucfirst($userName),
                'restaurant_name' => $restaurant->restaurant_name
            );
            $notificationJsonArray = array('user_id' => $reservationInvitationDetails [0] ['to_id'], 'username' => ucfirst($userName), 'restaurant_id' => $reservationInvitationDetails [0] ['restaurant_id'], 'restaurant_name' => $restaurant->restaurant_name);
            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
            $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);

            $replacementData = array('friends' => ucfirst($userName), 'restaurant_name' => $restaurant->restaurant_name);
            $otherReplacementData = array();
            $feed = array(
                'restaurant_id' => $reservationInvitationDetails [0] ['restaurant_id'],
                'restaurant_name' => $restaurant->restaurant_name,
                'user_name' => ucfirst($userName),
                'img' => [],
                'friend_id' => $reservationInvitationDetails [0] ['user_id']
            );
            $activityFeed = $commonFunctiion->addActivityFeed($feed, 7, $replacementData, $otherReplacementData);
            return true;
        } else {
            return false;
        }
    }

    public function takePoints($points, $userId, $refId = null, $takeThisUserId = false) {
        $userIdFromSession = StaticFunctions::getUserSession()->getUserId();
        if ($userIdFromSession && !$takeThisUserId) {
            $userId = $userIdFromSession;
        }
        $userPointsModel = new UserPoint ();
        $userPointsModel->user_id = $userId;
        $userPointsModel->point_source = $points['id'];
        $userPointsModel->ref_id = $refId;
        $userPointsModel->delete();
        $userModel = new User ();
        $currentPoints = $userModel->countUserPoints($userId);
        $totalPoints = 0;
        if (!empty($currentPoints) && $currentPoints [0] ['points'] != NULL && $currentPoints [0] ['points'] > 0) {
            $totalPoints = $currentPoints [0] ['points'] - $points ['points'];
        }
        return $userModel->updateUserPoint($userId, $totalPoints);
    }

    public function twitterLogin($data) {
        $userModel = StaticFunctions::getServiceLocator()->get(User::class);
        $userAccountModel = StaticFunctions::getServiceLocator()->get(UserAccount::class);
//        if (!isset($data ['email']) && empty($data ['email'])) {
//            throw new \Exception('User email is not valid');
//        }

        if (!isset($data ['accessToken']) && empty($data ['accessToken'])) {
            throw new \Exception('Required access token', 400);
        }

        if (!isset($data ['displayName']) && empty($data ['displayName'])) {
            throw new \Exception('User display name is not valid', 400);
        }

        if (!isset($data ['tw_uid']) && empty($data ['tw_uid'])) {
            throw new \Exception('Twitter user id is required', 400);
        }

        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location', array());
        $currentDateTime = $this->userCityTimeZone($locationData);
        $cityId = isset($locationData ['city_id']) ? $locationData ['city_id'] : "";
        $userModel->city_id = $cityId;
        $userAccountModel->user_name = isset($data ['displayName']) ? $data ['displayName'] : '';
        $userAccountModel->first_name = isset($data ['first_name']) ? $data ['first_name'] : '';
        $userAccountModel->last_name = isset($data ['last_name']) ? $data ['last_name'] : '';
        $userAccountModel->user_source = 'tw';
        $userModel->last_login = $currentDateTime;
        $userAccountModel->access_token = isset($data ['accessToken']) ? $data ['accessToken'] : '';
        $userAccountModel->session_token = isset($data ['tw_uid']) ? $data ['tw_uid'] : '';
        $userModel->update_at = $currentDateTime;
        $userModel->display_pic_url = isset($data ['image_url']) ? $data ['image_url'] : '';
        $userModel->display_pic_url_normal = isset($data ['image_url']) ? $data ['image_url'] : '';
        $userModel->display_pic_url_large = isset($data ['image_url']) ? $data ['image_url'] : '';
        $userModel->email = isset($data ['email']) ? $data ['email'] : '';
        $userModel->phone = isset($data ['phone']) ? $data ['phone'] : '';
        $userModel->newsletter_subscribtion = 0;
        $userModel->status = 1;
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $webUrl = PROTOCOL . $config['constants']['web_url'];

        $userLoginDetail = array();
        $userLoginDetail ['first_name'] = $$userAccountModel->first_name;
        $userLoginDetail ['last_name'] = $userAccountModel->last_name;
        $userLoginDetail ['phone'] = $userModel->phone;
        $userLoginDetail ['newsletter_subscribtion'] = $userModel->newsletter_subscribtion;
        $userLoginDetail ['profile_image_url'] = $userModel->display_pic_url;
        $userLoginDetail ['email'] = $userModel->email;
        $joins = array();
        $joins [] = array(
            'name' => array(
                'ua' => 'user_account'
            ),
            'on' => 'users.id = ua.user_id',
            'columns' => array(
                'user_source',
                'access_token',
                'session_token'
            ),
            'type' => 'inner'
        );

        $options = array(
            'columns' => array(
                '*'
            ),
            'where' => array('ua.session_token' => $userAccountModel->session_token),
            'joins' => $joins,
        );
        $userDetail = $userModel->getUserDetail($options);

        if ($userDetail) {
            $userModel->id = $userDetail ['id'];
            $userModel->created_at = $userDetail ['created_at'];
            $userModel->password = $userDetail ['password'];
            $userModel->points = $userDetail ['points'];
            $userModel->display_pic_url = $userDetail ['display_pic_url'];
            //$userModel->userRegistration();
            $session->setUserId($userDetail ['id']);
            $userLoginDetail["id"] = $userDetail ['id'];
            $userLoginDetail["first_name"] = $userDetail['first_name'];
            $userLoginDetail["last_name"] = $userDetail['last_name'];
            $userLoginDetail['phone_number'] = $userDetail['phone'];
            $userLoginDetail['email'] = $userDetail['email'];
            $userAccountModel->user_id = $userDetail ['id'];
            $userAccountModel->userAccountRegistration();
            if (isset($data['loyality_code']) && !empty($data['loyality_code'])) {
                $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;
                $referralDineMore = array(
                    "loyality_code" => $data['loyality_code'],
                    "referral_code" => $referralCode,
                    "user_id" => $userDetail ['id'],
                    "email" => $userDetail ['email'],
                    "first_name" => $userDetail ['first_name']
                );

                $this->existUserJoinDineMoreByReferral($referralDineMore, $userDetail ['id']);
            }
            $data = array(
                'email' => $userDetail ['email']
            );
            $session->setUserDetail($data);
            $session->save();
            ########### Associate user through deeplink ##############
            $open_page_type = (isset($data['open_page_type']) && !empty($data['open_page_type'])) ? $data['open_page_type'] : "";
            $refId = (isset($data['refId']) && !empty($data['refId'])) ? $data['refId'] : "";
            $userId = $userDetail ['id'];
            $this->userId = $userDetail ['id'];
            $userEmail = $userDetail ['email'];
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            ########################################## 
            $userLoginDetail['dine_and_more'] = (int) 0;
            if ($this->isRegisterWithAnyRestaurant()) {
                $userLoginDetail['dine_and_more'] = (int) 1;
            }
            $userLoginDetail['previously_registered'] = (int) 1;
            if ($userDetail['created_at'] == $userDetail['update_at']) {
                $cleverTap = array(
                    "user_id" => $userDetail ['id'],
                    "name" => (isset($userDetail ['last_name']) && !empty($userDetail ['last_name'])) ? $userDetail['first_name'] . " " . $userDetail ['last_name'] : $userDetail['first_name'],
                    "email" => $userDetail['email'],
                    "identity" => $userDetail['email'],
                    "eventname" => "signed_to_app",
                    "is_register" => "yes",
                    "date" => $currentDateTime,
                    "event" => 1,
                );
                $this->createQueue($cleverTap, 'clevertap');
            }
            $userModel->update(array(
                'last_login' => $currentDateTime,
                'update_at' => $currentDateTime
            ));

            return $userLoginDetail;
        } else {
            if (empty($data ['email'])) {
                throw new \Exception("tw", 400);
            }
            //check email id exist or not 
            $emailExist = $userModel->getUserDetail(array(
                'columns' => array(
                    'countemail' => new \Zend\Db\Sql\Expression('COUNT(*)'),
                ),
                'where' => array(
                    'email' => $userModel->email
                )
            ));
            if ($emailExist['countemail'] == 1) {
                throw new \Exception("Email address already exist", 400);
            }

            $referralCode = (isset($data['referral_code']) && !empty($data['referral_code'])) ? $data['referral_code'] : false;
            $loyalityCode = (isset($data['loyality_code']) && !empty($data['loyality_code'])) ? $data['loyality_code'] : "";

            ############## Loyality Program Registration code validation #############
            if ($loyalityCode) {
                if (!$this->parseLoyaltyCode($loyalityCode)) {
                    throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.");
                }
            }
            ##########################################################################
            //check email id exist or not 
            $userModel->user_name = isset($data ['displayName']) ? $data ['displayName'] : '';
            $userModel->first_name = isset($data ['first_name']) ? $data ['first_name'] : '';
            $userModel->last_name = isset($data ['last_name']) ? $data ['last_name'] : '';
            $userModel->created_at = $currentDateTime;
            $userDetail = $userModel->userRegistration();
            $userAccountModel->user_id = $userDetail ['id'];
            $userAccountModel->userAccountRegistration();
            $session->setUserId($userModel->id);
            $data = array(
                'email' => $userDetail ['email']
            );

            $session->setUserDetail($data);
            $session->save();
            $userLoginDetail['id'] = $userModel->id;

            ############## Loyality Program Registration #############  
            $register_notif_count = 1;
            if ($loyalityCode) {
                $this->registerRestaurantServer();
                $this->userId = $userModel->id;
                $this->first_name = $userModel->first_name;
                $this->email = $userDetail ['email'];
                $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
                $this->loyaltyCode = $loyalityCode;
                $this->mailSmsRegistrationPassword($template);
                $this->dineAndMoreAwards("awardsregistration");
                $register_notif_count = 2;
            }
            ##########################################################
  
            $points = $this->getAllocatedPoints('socialRegister');
            $message = 'All life is a game. Here are 100 points to get you ahead of the game. Don\'t worry, it\'s not cheating.';
            $this->givePoints($points, $userModel->id, $message);

            $feed_name = $userModel->first_name . ' ' . $userModel->last_name;
            $feed = array(
                'user_id' => $userModel->id,
                'user_email' => $userDetail ['email'],
                'user_name' => ucfirst($feed_name)
            );
            $replacementData = array('message' => 'test');
            $otherReplacementData = array('user_name' => ucfirst($feed_name));

            $commonFunction = new \MCommons\CommonFunctions();
            $activityFeed = $commonFunction->addActivityFeed($feed, 53, $replacementData, $otherReplacementData);

            #############################################################################
            ############### Assign Promocode ##################
            $this->assignPromocodeOnFirstRegistration($userModel->id, $userModel->user_name, $userModel->email);
            ###################################################   
            ########## Notification to user on first Registration ########
            $notificationMsg = 'Welcome to Munch Ado! From now on, we’ll be helping you get from hangry to satisfied.';
            $channel = "mymunchado_" . $userModel->id;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userModel->id,
                "type" => 'registration',
                "restaurantId" => '0',
                'curDate' => $currentDateTime
            );
//            $userNotificationModel = StaticFunctions::getServiceLocator()->get(UserNotification::class);
//            $response = $userNotificationModel->createPubNubNotification($notificationArray);
//            $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);

            if (!$loyalityCode || strtoupper($loyalityCode) === MUNCHADO_DINE_MORE_CODE) {
                $template = 'user-registration';
                $layout = 'email-layout/default_register';
                $variables = array(
                    'username' => $userLoginDetail['first_name'],
                    'hostname' => $webUrl
                );
                $mailData = array('recievers' => $userLoginDetail['email'], 'layout' => $layout, 'template' => $template, 'variables' => $variables);
                $this->sendRegistrationEmail($mailData);
            }

            if (isset($data['referalid']) && $data['referalid'] != null) {
                $this->invitationAccepted($data['referalid'], $userLoginDetail['email'], true);
            }
            $this->createSettings($userLoginDetail['id'], $userLoginDetail['newsletter_subscribtion']);

            ######### Intigration of user reffer invitation ############
            if ($referralCode) {
                $this->saveReferredUserInviterData($userLoginDetail['id'], $referralCode);
            }
            ############################################################
            ########### Associate user through deeplink ##############
            $open_page_type = (isset($data['open_page_type']) && !empty($data['open_page_type'])) ? $data['open_page_type'] : "";
            $refId = (isset($data['refId']) && !empty($data['refId'])) ? $data['refId'] : "";
            $userId = $userModel->id;
            $userEmail = $userDetail ['email'];
            if (!empty($open_page_type)) {
                $this->associateInvitation($open_page_type, $refId, $userId, $userEmail);
            }
            ########################################## 
            $userLoginDetail['previously_registered'] = (int) 0;
            $userLoginDetail['register_notif_count'] = $register_notif_count;
            $clevertapData = array(
                "user_id" => $userModel->id,
                "name" => ($userModel->last_name) ? $userModel->first_name . " " . $userModel->last_name : $userModel->first_name,
                "email" => $userModel->email,
                "currentDate" => $currentDateTime,
                "source" => $userAccountModel->user_source,
                "loyalitycode" => ($loyalityCode) ? $loyalityCode : false,
                "restname" => ($loyalityCode) ? $this->restaurant_name : "",
                "restid" => ($loyalityCode) ? $this->restaurantId : "",
                "eventname" => ($loyalityCode) ? "dine_and_more" : "general",
            );
            if ($referralCode) {
                $clevertapData['refferralPoint'] = $this->referralPoint;
            }
            $this->clevertapRegistrationEvent($clevertapData);
            return $userLoginDetail;
        }
    }

    public function invitationDecline($id, $registeredWithDifferentEmail = false, $newUser = false) {
        $invitationModel = new UserFriendsInvitation ();
        $data = array('invitation_status' => '2');
        $predicate = array('id' => $id);
        $invitationModel->abstractUpdate($data, $predicate);
        return true;
    }

    public function emailSubscription($data) {
        $recievers = array(
            $data ['recievers']
        );
        $template = $data ['template'];
        $layout = $data['layout'];
        $variables = $data ['variables'];
        $subject = $data['subject'];
        $sender = (isset($data['sender']) && !empty($data['sender'])) ? $data['sender'] : array();
        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout,
            'sender' => $sender,
        );

        // #################
        $this->sendMailSubscription($emailData);
    }

    public function sendMailSubscription($data) {
        if (!empty($data['sender'])) {
            $sender = 'notifications@munchado.com'; //$data['sender']['fromEmail'];
            $sendername = $data['sender']['fromName'];
        } else {
            $sender = 'notifications@munchado.com';
            $sendername = "Munchado";
        }
        // $c = StaticFunctions::getServiceLocator()->get('config');

        $recievers = array(
            $data ['receiver']
        );
        $template = "email-template/" . $data ['template'];
        $layout = 'email-layout/' . $data['layout'];

        $subject = $data ['subject'];
        $resquedata = array(
            'sender' => $sender,
            'sendername' => $sendername,
            'variables' => $data ['variables'],
            'receivers' => $recievers,
            'template' => $template,
            'layout' => $layout,
            'subject' => $subject
        );
        StaticFunctions::resquePush($resquedata, 'SendEmail');
        // StaticFunctions::sendMail ( $sender, $sendername, $recievers, $template, $layout, $data ['variables'], $data ['subject'] );
    }

    public function givePointsEmailSubscription($points, $userId, $message = null) {

        $userPointsModel = new UserPoint ();
        $data = array(
            'user_id' => $userId,
            'point_source' => $points ['id'],
            'points' => $points ['points'],
            'created_at' => StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT),
            'status' => 1,
            'points_descriptions' => $message,
            'ref_id' => $userId
        );
        $userPointsModel->createPointDetail($data);
        $userModel = new User ();
        $currentPoints = $userModel->countUserPoints($userId);
        if (!empty($currentPoints)) {
            $totalPoints = $currentPoints [0] ['points'] + $points ['points'];
        } else {
            $totalPoints = $points ['points'];
        }
        $userModel->updateUserPoint($userId, $totalPoints);
    }

    public function assignPromocodeOnFirstRegistration($userId, $userName, $userEmail, $existUser = false) {
        if (COUPON_SUBSCRIPTION == 1) {

            #####################
            $session = StaticFunctions::getUserSession();
            $locationData = $session->getUserDetail('selected_location');
            $currentDate = $this->userCityTimeZone($locationData);
            #####################            
            $promocodes = StaticFunctions::getServiceLocator()->get(\Restaurant\Model\Promocodes::class);
            $pDetails['start_on'] = $currentDate;
            $currentDate1 = new \DateTime($currentDate);
            $currentDate1->add(new \DateInterval(PROMOCODE_ENDDATE));
            $endDate = $currentDate1->format('Y-m-d H:i:s');
            $pDetails['end_date'] = $endDate;
            $pDetails['promocodeType'] = 2;
            $pDetails['discount'] = PROMOCODE_FIRST_REGISTRATION;
            $pDetails['discount_type'] = 'flat';
            $pDetails['status'] = 1;
            $pDetails['deal_for'] = 'delivery/takeout/dine-in';
            $pDetails['title'] = '$' . PROMOCODE_FIRST_REGISTRATION . ' for You';
            $pDetails['description'] = 'Enjoy free order up to $' . PROMOCODE_FIRST_REGISTRATION . ' on pre-paid reservation or delivery or take-out orders';
            $addPromocode = $promocodes->insert($pDetails);
            if ($addPromocode) {
                $upDetail['promo_id'] = $promocodes->id;
                $upDetail['user_id'] = $userId;
                $upDetail['reedemed'] = 0;
                $upDetail['order_id'] = 0;
                $userPromocode = StaticFunctions::getServiceLocator()->get(UserPromoCodes::class);
                $userPromocode->insert($upDetail);
            }
            return array('discount' => PROMOCODE_FIRST_REGISTRATION, 'endDate' => $currentDate1->format('F j, Y H:i:s'));
        }

        return array();
    }

    public function userAvatar($action, $action_info = false) {

        switch ($action) {
            case 'invite_friend':     // for VIP
                $reponse = $this->addUpdateUserAvatar('munch_maven');
                break;
            case 'order': //Super Health / Influencer / pizza / fumunchu / delivery / takeout
                //$order_detail = get_order_detail($action_info);
                // influncer
                if ($action_info === 'G') {
                    $reponse = $this->addUpdateUserAvatar('munch_maven');
                }
                // pizza
                if ($action_info === 'pizza') {
                    $reponse = $this->addUpdateUserAvatar('cheesy_triangle');
                }
                // burgers
                if ($action_info === 'burgers') {
                    $reponse = $this->addUpdateUserAvatar('sir_loin');
                }
                // fu_munchu
                if ($action_info === 'asian') {
                    $reponse = $this->addUpdateUserAvatar('fu_munchu');
                }
                // delivery
                if ($action_info === 'delivery') {
                    $reponse = $this->addUpdateUserAvatar('home_eater');
                }
                // takeout
                if ($action_info === 'takeout') {
                    $reponse = $this->addUpdateUserAvatar('takeout_artist');
                }
                // Super Health
                if ($action_info === 'health food') {
                    $reponse = $this->addUpdateUserAvatar('health_nut');
                }
                break;
            case 'reservation':
                $reponse = $this->addUpdateUserAvatar('vip');
                break;
            case 'review':
                $reponse = $this->addUpdateUserAvatar('food_pundit');
                break;
            case 'tip':
                $reponse = $this->addUpdateUserAvatar('food_pundit');
                break;
            case 'checkin':
                if ($action_info) {
                    $reponse = $this->addUpdateUserAvatar('fu_munchu');
                    // Super Health
                    if ($action_info === 'health food') {
                        $reponse = $this->addUpdateUserAvatar('health_nut');
                    }
                    // pizza
                    if ($action_info === 'pizza') {
                        $reponse = $this->addUpdateUserAvatar('cheesy_triangle');
                    }
                    // burgers
                    if ($action_info === 'burgers') {
                        $reponse = $this->addUpdateUserAvatar('sir_loin');
                    }
                    // fu_munchu
                    if ($action_info === 'asian') {
                        $reponse = $this->addUpdateUserAvatar('fu_munchu');
                    }
                } else {
                    $reponse = $this->addUpdateUserAvatar('fu_munchu');
                }
                break;
        }
    }

    public function addUpdateUserAvatar($type) {
        $userId = StaticFunctions::getUserSession()->getUserId();
        if (!empty($type)) {
            $avatar = new \User\Model\Avatar();
            $commonFunctions = new CommonFunctions();
            $options = array('columns' => array('id', 'name', 'avatar_image', 'message', 'action', 'action_number'), 'where' => array('status' => 1, 'type' => $type));
            $avatarTypeArray = $avatar->find($options)->toArray();
            if ($avatarTypeArray) {
                $unlocked = 0;
                $remaining = 0;
                $avatarId = $avatarTypeArray[0]['id'];
                $action_number = $avatarTypeArray[0]['action_number'];
                //pr($avatarTypeArray,true);
                //muncher requirement start
                $avtar_name = isset($avatarTypeArray[0]['name']) ? $avatarTypeArray[0]['name'] : '';
                $avtar_Message = isset($avatarTypeArray[0]['message']) ? $avatarTypeArray[0]['message'] : '';
                $avtar_Image = isset($avatarTypeArray[0]['avatar_image']) ? $avatarTypeArray[0]['avatar_image'] : '';
                $avtar_Other = isset($avatarTypeArray[0]['action']) ? $avatarTypeArray[0]['action'] : '';
                //muncher requirement end
                $userAvatar = new \User\Model\UserAvatar();
                $options = array('columns' => array('id', 'action_count', 'total_earned'), 'where' => array('user_id' => $userId, 'avatar_id' => $avatarId));
                $userAvatarDetail = $userAvatar->find($options)->toArray();
                $currentDate = StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT);
                if (!empty($userAvatarDetail[0])) {      // update existing avatar of user
                    $updateData = array();
                    $total_action = $userAvatarDetail[0]['action_count'] + 1;
                    $updateData['action_count'] = $total_action;
                    if ($total_action % $action_number == 0) {
                        $userModel = new \User\Model\User();
                        $userName = $userModel->getFirstName($userId);
                        $updateData['total_earned'] = $userAvatarDetail[0]['total_earned'] + 1;
                        $unlocked = 1;

                        //feed for muncher
                        $replacementData = array('#avatar_name' => $avtar_name);
                        $otherReplacementData = array();
                        $feed = array(
                            'muncher_id' => $avatarId,
                            'muncher_name' => $avtar_name,
                            'user_name' => ucfirst($userName),
                            'img' => array($avtar_Image),
                            'tip' => NULL
                        );
                        $activityFeed = $commonFunctions->addActivityFeedForMuncher($feed, 21, $replacementData, $otherReplacementData, $avtar_Message);
                    } else {
                        $remaining = $action_number - ($total_action / $action_number);
                    }
                    $updateData['date_earned'] = $currentDate;
                    $userAvatar->id = $userAvatarDetail[0]['id'];
                    $userAvatar->insert($updateData);
                } else { // add new record
                    $insertData = array();
                    $insertData['user_id'] = $userId;
                    $insertData['avatar_id'] = $avatarId;
                    $insertData['action_count'] = 1;
                    $insertData['date_earned'] = $currentDate;
                    $insertData['total_earned'] = 0;
                    $insertData['status'] = 1;
                    $userAvatar->insert($insertData);
                }
                return (array('unlocked' => $unlocked, 'remaining' => $remaining));
            }
        }
    }

    public function myAvatar($avatarId) {
        $userId = StaticFunctions::getUserSession()->getUserId();
        $userAvatar = StaticFunctions::getServiceLocator()->get(UserAvatar::class);

        $options = [
            'column' => ['id', 'action_count', 'total_earned', 'date_earned'],
            'where' => ['user_id' => $userId, 'avatar_id' => $avatarId],
        ];
        $userAvatarDetail = $userAvatar->find($options)->toArray();
        return $userAvatarDetail;
    }

    public function myAvatarOnly($avatarId) {
        $userId = StaticFunctions::getUserSession()->getUserId();
        $userAvatar = StaticFunctions::getServiceLocator()->get(UserAvatar::class);

        $options = [
            'column' => ['id', 'action_count', 'total_earned', 'date_earned'],
            'where' => ['user_id' => $userId, 'avatar_id' => $avatarId]
        ];
        $userAvatarDetail = $userAvatar->find($options)->toArray();
        $userMuncher['total_earned'] = 0;
        $userMuncher['date_earned'] = '';
        if (count($userAvatarDetail) > 0) {
            foreach ($userAvatarDetail as $key => $val) {
                $userMuncher['total_earned'] += $val['total_earned'];
                $userMuncher['date_earned'] = $val['date_earned'];
            }
        }
        return $userMuncher;
    }

    public function getRegistrationCloseUrl($url = false) {
        if ($url) {
            $urlArray = parse_url($url);
            return $url = $urlArray['scheme'] . "://" . $urlArray['host'] . "/registration/close";
        } else {
            return $url;
        }
    }

    public function userLikeFeed($userId, $FeedId) {
        $feedBookmark = new \Bookmark\Model\FeedBookmark();
        $feedBookmark->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $opt1 = array(
            'where' => array('feed_id' => $FeedId, 'user_id' => $userId)
        );
        if ($feedBookmark->find($opt1)->toArray()) {
            return true;
        } else {
            return false;
        }
    }

    public function userCommentFeed($userId, $FeedId) {
        $feedComment = new \User\Model\FeedComment();
        $feedComment->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $opt2 = array(
            'where' => array('feed_id' => $FeedId, 'user_id' => $userId)
        );
        if ($feedComment->find($opt2)->toArray()) {
            return true;
        } else {
            return false;
        }
    }

    public function getAllAvatar($avatarId = false) {
        $allAvatar = $options = [];
        $avatarModel = StaticFunctions::getServiceLocator()->get(Avatar::class);
        $options['columns'] = [
            'id', 'avatar', 'name', 'type', 'avatar_image',
            'message' => 'temp_message',
            'action', 'action_number',
        ];
        if ($avatarId) {
            $options['where'] = ['id' => $avatarId];
        }
        if ($avatarModel->find($options)) {
            $allAvatar = $avatarModel->find($options)->toArray();
        }
        return $allAvatar;
    }

    public function getMyAvatar($allAvatar = []) {
        $myAvatar = [];
        if (!empty($allAvatar)) {
            $i = 0;
            foreach ($allAvatar as $key => $val) {
                $myEarnedAvatar = $this->myAvatarOnly($val['id']);
                $myAvatar[$i]['avatar_id'] = (int) $val['id'];
                $myAvatar[$i]['avatar'] = $val['avatar'];
                $myAvatar[$i]['avatar_name'] = $val['name'];
                $myAvatar[$i]['avatar_image'] = $val['avatar_image'];
                $myAvatar[$i]['message'] = $val['message'];
                if ($myEarnedAvatar) {
                    $earnedMuncherCount = $myEarnedAvatar['total_earned'] % $val['action_number'];
                    $myAvatar[$i]['date_earned'] = $myEarnedAvatar['date_earned'];
                    $myAvatar[$i]['muncher_earned_count'] = (int) $earnedMuncherCount;
                    $myAvatar[$i]['unlocked'] = ($earnedMuncherCount > 0) ? 1 : 0;
                } else {
                    $myAvatar[$i]['date_earned'] = '';
                    $myAvatar[$i]['muncher_earned_count'] = (int) 0;
                    $myAvatar[$i]['unlocked'] = (int) 0;
                }
                $i++;
            }
        }
        return $myAvatar;
    }

    public function getMyLastEarnedMuncher($userId = false) {
        if ($userId) {
            
        } else {
            $userId = StaticFunctions::getUserSession()->getUserId();
        }
        $userAvatar = StaticFunctions::getServiceLocator()->get(UserAvatar::class);
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');

        $options = array(
            'columns' => array('id', 'avatar_id', 'action_count', 'total_earned' => new \Zend\Db\Sql\Expression('sum(total_earned)'), 'date_earned'),
            'where' => new \Zend\Db\Sql\Predicate\Expression('user_id=' . $userId . ' AND total_earned >=1'),
            'order' => array(
                'date_earned' => 'desc'
            ),
            'limit' => 1
        );
        if ($userAvatar->find($options)->toArray()) {
            $response = $userAvatar->find($options)->toArray()[0];
            if ($response['avatar_id'] != '') {
                $options1 = array(
                    'columns' => array('name', 'type'),
                    'where' => array('id' => $response['avatar_id'])
                );
                $avatarModel = StaticFunctions::getServiceLocator()->get(Avatar::class);
                $response1 = $avatarModel->find($options1)->toArray()[0];
                $lastEarnAvatar['title'] = $response1['name'];
                $lastEarnAvatar['identifier'] = $config ['constants']['muncher_identifire'] [$response1['type']];
                $lastEarnAvatar['total_earned'] = $response['total_earned'];
            } else {
                $lastEarnAvatar = false;
            }
        } else {
            $lastEarnAvatar = false;
        }
        return $lastEarnAvatar;
    }

    public function isReviewUsefullCount($reviewId) {
        $feedback = StaticFunctions::getServiceLocator()->get(\User\Model\UserFeedback::class);
        $feedbackOption = array(
            'columns' => array(
                'total_usefull_count' => new \Zend\Db\Sql\Expression('count(id)'),
            ),
            'where' => array('review_id' => $reviewId, 'feedback' => 1),
        );
        $f = $feedback->find($feedbackOption)->toArray();
        if ($f) {
            return $f[0];
        } else {
            return [];
        }
    }

    public function isReviewUsefullForUser($reviewId, $userId) {
        $feedback = StaticFunctions::getServiceLocator()->get(\User\Model\UserFeedback::class);
        $feedbackOption = array(
            'columns' => array(
                'total_usefull_count' => new \Zend\Db\Sql\Expression('count(id)'),
                'feedback'
            ),
            'where' => array('review_id' => $reviewId, 'user_id' => $userId),
        );
        $f = $feedback->find($feedbackOption)->toArray();
        if ($f) {
            return $f[0];
        } else {
            return [];
        }
    }

    public function getUserLastActivity($userId = false, $version = false) {
        $feed = Null;
        if ($userId) {
            $activityFeedModel = new Model\ActivityFeed();
            $joins = array();
            $joins [] = array(
                'name' => 'users',
                'on' => 'users.id = activity_feed.user_id',
                'columns' => array(
                    'display_pic_url',
                ),
                'type' => 'left'
            );
            $joins [] = array(
                'name' => 'activity_feed_type',
                'on' => 'activity_feed_type.id = activity_feed.feed_type_id',
                'columns' => array(
                    'feed_type',
                ),
                'type' => 'left'
            );
            $lastVerion = explode('.', '1.0.9');
            $version = explode('.', $version);
            $feedByVersion = false;
            foreach ($version as $key => $v) {
                if ($v > $lastVerion[$key]) {
                    $feedByVersion = true;
                }
            }
            if (empty($version)) {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $userId . ') and activity_feed.status="1" and activity_feed.feed_type_id!="52"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => 1,
                    'offset' => 0,
                    'joins' => $joins
                );
            } else if (isset($feedByVersion) && $feedByVersion == true) {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id in (' . $userId . ') and activity_feed.status="1"'),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => 1,
                    'offset' => 0,
                    'joins' => $joins
                );
            } else {
                $options = array(
                    'where' => new \Zend\Db\Sql\Predicate\Expression('activity_feed.user_id=' . $userId . ' and activity_feed.status="1" and feed_type_id<= "52"'),
                    //'where' => array('activity_feed.status' => '1', 'activity_feed.user_id' => $userId),
                    'order' => array('added_date_time' => 'desc'),
                    'limit' => 1,
                    'offset' => 0,
                    'joins' => $joins
                );
            }
            $activityFeedModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $feed = $activityFeedModel->find($options)->toArray();
            $feedBookmark = new \Bookmark\Model\FeedBookmark();
            $feedBookmark->getDbTable()->setArrayObjectPrototype('ArrayObject');
            $feedComment = new \User\Model\FeedComment();
            $feedComment->getDbTable()->setArrayObjectPrototype('ArrayObject');
            if ($feed) {

                foreach ($feed as $key => $val) {
                    $opt1 = array(
                        'columns' => array('total_like' => new \Zend\Db\Sql\Expression('COUNT(id)')),
                        'where' => array('feed_id' => $val['id']));
                    $opt2 = array(
                        'columns' => array('total_comment' => new \Zend\Db\Sql\Expression('COUNT(id)')),
                        'where' => array('feed_id' => $val['id']));
                    $totalfeedbookmark = $feedBookmark->find($opt1)->toArray();
                    $totalfeedcomment = $feedComment->find($opt2)->toArray();
                    //feed update
                    $feedGet = explode('{', $val['feed']);
                    $feedGetForReview = explode('{', $val['feed']);
                    $feedGet = explode(',', $feedGet[1]);
                    $checkMess = '';
                    $checkReviewMessage = '';
                    $checkTipMessage = '';
                    $checkCaptionMessage = '';
                    foreach ($feedGet as $val2) {
                        $feedGet = explode(':', $val2);
                        if (in_array('"checkinmessage"', $feedGet)) {
                            $checkMess = rtrim($feedGet[1], '"');
                            $checkMess = ltrim($checkMess, '"');
                        }
                        if (in_array('"tip"', $feedGet)) {
                            $checkTipMessage = rtrim($feedGet[1], '"');
                            $checkTipMessage = ltrim($checkTipMessage, '"');
                        }
                        if (in_array('"caption"', $feedGet)) {
                            $checkCaptionMessage = rtrim($feedGet[1], '"');
                            $checkCaptionMessage = ltrim($checkCaptionMessage, '"');
                        }
                        if (in_array('"review"', $feedGet)) {
                            $feedGetReview = explode(',', $feedGetForReview[2]);
                            foreach ($feedGetReview as $reviewVal) {
                                if ($reviewVal) {
                                    $reviewValarray = explode(':', $reviewVal);
                                    if ($reviewValarray[0] == '"review_desc"') {
                                        $checkReviewMessage = rtrim($reviewValarray[1], '"');
                                        $checkReviewMessage = ltrim($checkReviewMessage, '"');
                                        $checkReviewMessage = rtrim($checkReviewMessage, '}');
                                    }
                                }
                            }
                        }
                    }
                    $feedJsonVal = json_decode($val['feed'], true);
                    if (!empty($feedJsonVal['feed_for_other'])) {
                        $feed[$key]['others'] = 1;
                    } else {
                        $feed[$key]['others'] = 0;
                    }
                    if (key_exists('checkinmessage', $feedJsonVal)) {
                        unset($feedJsonVal['checkinmessage']);
                    }
                    if (isset($feedJsonVal['review']) && count($feedJsonVal['review']) > 0) {
                        unset($feedJsonVal['review']['review_desc']);
                        $feedJsonVal['review']['review_desc'] = $checkReviewMessage;
                    }
                    $feed[$key]['total_like'] = (int) $totalfeedbookmark[0]['total_like'];
                    $feed[$key]['total_comment'] = (int) $totalfeedcomment[0]['total_comment'];
                    if ($feed[$key]['others'] == 1) {
                        $feed[$key]['user_like'] = ($this->userLikeFeed($userId, $val['id'])) ? 1 : 0;
                    } else {
                        $feed[$key]['user_like'] = ($this->userLikeFeed($val['user_id'], $val['id'])) ? 1 : 0;
                    }


                    $feed[$key]['user_comment'] = ($this->userCommentFeed($val['user_id'], $val['id'])) ? 1 : 0;
                    $feed[$key]['feedinfo'] = $feedJsonVal;
                    $feed[$key]['feedinfo']['checkinmessage'] = $checkMess;
                    if ($checkTipMessage != '') {
                        $feed[$key]['feedinfo']['tip'] = $checkTipMessage;
                    }
                    if ($checkCaptionMessage != '') {
                        $feed[$key]['feedinfo']['caption'] = $checkCaptionMessage;
                    }
                    $feed[$key]['feedinfo']['feed_for_other'] = isset($feed[$key]['feedinfo']['feed_for_other']) && $feed[$key]['feedinfo']['feed_for_other'] != '' ? str_replace('â€™', "'", $feed[$key]['feedinfo']['feed_for_other']) : '';
                    $feed[$key]['feedinfo']['text'] = isset($feed[$key]['feedinfo']['text']) && $feed[$key]['feedinfo']['text'] != '' ? str_replace('â€™', "'", $feed[$key]['feedinfo']['text']) : '';
                    $feed[$key]['display_pic_url'] = $this->findImageUrlNormal($val['display_pic_url'], $val['user_id']);
                    unset($feed[$key]['feed'], $feed[$key]['feed_for_others'], $feed[$key]['event_date_time'], $feed[$key]['status'], $feed[$key]['feed_type_id']);
                }
            }
        }
        return $feed;
    }

    public function userPromocode($userId, $currentDateTimeUnixTimeStamp) {
        $userPromocodesDetails = array();
        $userPromocodeModel = new \Restaurant\Model\UserPromocodes();
        $userPromocodeModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $joins = array();
        $joins [] = array(
            'name' => array(
                'p' => 'promocodes'
            ),
            'on' => new \Zend\Db\Sql\Expression('p.id = promo_id AND p.status = 1'),
            'columns' => array(
                'user_promocode_id' => new \Zend\Db\Sql\Expression('user_promocodes.id'),
                'start_on',
                'end_date',
                'discount',
                'discount_type',
                'minimum_order_amount',
                'slots',
                'days',
                'deal_for',
                'title',
                'description'
            ),
            'type' => 'right'
        );
        $options = array(
            'columns' => array(
                'user_id'
            ),
            'where' => array(
                'reedemed' => 0,
                'user_id' => $userId
            ),
            'order' => 'p.start_on',
            'joins' => $joins
        );
        $userPromocodes = $userPromocodeModel->find($options)->toArray();
        if (!empty($userPromocodes)) {
            foreach ($userPromocodes as $key => $val) {
                $promocodeStartTimestamp = strtotime($userPromocodes[$key]['start_on']);
                $promocodeEndTimestamp = strtotime($userPromocodes[$key]['end_date']);
                if ($currentDateTimeUnixTimeStamp <= $promocodeEndTimestamp && $currentDateTimeUnixTimeStamp >= $promocodeStartTimestamp) {
                    $userPromocodesDetails = $userPromocodes[$key];
                    return $userPromocodesDetails;
                }
            }
        }
        return $userPromocodesDetails;
    }

    /*     * ***Send SMS **** */

    public function sendSmsonTransactionCount($userId) {
        $userOrderModel = new UserOrder ();
        $ordersoptions = array(
            'columns' => array(
                'ordercount' => new Expression('COUNT(user_orders.id)'),
            ),
            'where' => array('user_id ="' . $userId . '"')
        );
        $userOrderModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $allOrders = $userOrderModel->find($ordersoptions)->toArray();
        $reservationModel = new UserReservation();
        $options = array(
            'columns' => array(
                'ordercount' => new Expression('COUNT(id)'),
            ),
            'where' => array('user_id ="' . $userId . '"')
        );
        $reservationModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $allReservation = $reservationModel->find($options)->toArray();
        $totalTransactionCount = $allOrders[0]['ordercount'] + $allReservation[0]['ordercount'];
        return $totalTransactionCount;
//        if ($totalTransactionCount == 2) {
//            $userSmsData = array();
//            if (strtotime($reservationOrderRecord[0]['last_order']) > strtotime($reservationOrderRecord[1]['last_order'])) {
//                $userSmsData['user_mob_no'] = $reservationOrderRecord[0]['phone'];
//                $userSmsData['message'] = "Have you added us to your contacts yet? How cool would it be to get a text from \"The Harbinger\" of Food” or just \"Munch Ado\" every time you ordered food?";
//            } else {
//                $userSmsData['user_mob_no'] = $reservationOrderRecord[1]['phone'];
//                $userSmsData['message'] = "Have you added us to your contacts yet? How cool would it be to get a text from \"The Harbinger\" of Food” or just \"Munch Ado\" every time you ordered food?";
//            }
//            //StaticFunctions::sendSmsClickaTell($userSmsData,$userId);
//        }
    }

    public function getFirstTranSactionUser() {
        $session = StaticFunctions::getUserSession();
        $userId = $session->getUserId();
        $userOrderModel = new UserOrder();
        $this->userId = $userId;
        if (!$this->isRegisterWithRestaurant($userId)) {
            //$reservationModel = new UserReservation();
            $orders = current($userOrderModel->getTotalUserFirstOrders($userId, $this->restaurantId));
            //$reservations = current($reservationModel->getTotalPrePaidReservations($userId));
            //$totaltransaction = $orders['total_order'] + $reservations['total_reservation'];
            $totaltransaction = $orders['total_order'];
            return $totaltransaction;
        }
        return false;
    }

    public function associateInvitation($open_page_type = false, $refId = false, $userId = false, $userEmail = false) {
        $response = false;
        if (!empty($refId) && $refId) {
            $session = StaticFunctions::getUserSession();
            $locationData = $session->getUserDetail('selected_location');
            $currentDate = $this->userCityTimeZone($locationData);
            $sl = StaticFunctions::getServiceLocator();
            $config = $sl->get('Config');

            if (!empty($open_page_type) && $open_page_type == 3) {
                $reservationModel = StaticFunctions::getServiceLocator()->get(UserReservation::class);
                $reservationStatus = isset($config ['constants'] ['reservation_status']) ? $config ['constants'] ['reservation_status'] : array();
                $upcomingCondition = array(
                    'reservationIds' => array($refId),
                    'currentDate' => $currentDate,
                    'userId' => $userId,
                    'status' => array(
                        $reservationStatus ['upcoming'],
                        $reservationStatus ['confirmed']
                    ),
                    'orderBy' => 'time_slot ASC'
                );

                $reservationDetail = $reservationModel->getReservationDetailForMob($upcomingCondition);

                if (count($reservationDetail) > 0) {
                    $userInvitationModel = StaticFunctions::getServiceLocator()->get(UserInvitation::class);

                    $userInvitationModel->user_id = $reservationDetail[0]['user_id'];
                    $userInvitationModel->to_id = $userId;
                    $userInvitationModel->restaurant_id = $reservationDetail[0]['restaurant_id'];
                    $userInvitationModel->message = '';
                    $userInvitationModel->msg_status = 0;
                    $userInvitationModel->reservation_id = $refId;
                    $userInvitationModel->friend_email = $userEmail;
                    $userInvitationModel->user_type = 1;
                    $userInvitationModel->created_on = $currentDate;

                    $get_user_invitation = $userInvitationModel->getUserInvitation(array(
                        'columns' => array(
                            'id',
                            'msg_status'
                        ),
                        'where' => array(
                            'reservation_id' => $userInvitationModel->reservation_id,
                            'user_id' => $userInvitationModel->user_id,
                            'to_id' => $userInvitationModel->to_id
                        )
                    ));
                    if (!$get_user_invitation) {
                        $user_reservation_invitation = $userInvitationModel->createInvitation();
                    }
                }

                $response = true;
            } elseif (!empty($open_page_type) && $open_page_type == 10) {
                $userFriendsInvitationModel = new UserFriendsInvitation();
                $userId = $refId; //userId (who had invite friend)
                $session = StaticFunctions::getUserSession();
                $token = StaticFunctions::getUserSession()->token;
                $loggedInUserId = $session->getUserId();
                $loggedInUserEmail = $session->getUserDetail('email');
                $locationData = $session->getUserDetail('selected_location');
                $currentDate = $this->userCityTimeZone($locationData);
                $date = strtotime($currentDate);
                $expiredOn = strtotime("+7 day", $date);
                $expiredOn = date('Y-m-d H:i:s', $expiredOn);

                $friendOptions = array('columns' => array('id'),
                    'where' => array('user_id' => $userId, 'email' => $loggedInUserEmail, 'invitation_status' => array(0, 1))
                );

                $getFriendExists = $userFriendsInvitationModel->find($friendOptions)->toArray();
                if (!$getFriendExists) {
                    $insertdata = array(
                        'user_id' => $userId,
                        'email' => $loggedInUserEmail,
                        'source' => 'munch',
                        'created_on' => $currentDate,
                        'token' => $token,
                        'expired_on' => $expiredOn,
                        'status' => '1'
                    );

                    $userFriendsInvitationModel->createUserInvitation($insertdata);
                }
                return true;
            }
        }
        return $response;
    }

    public function invitationReservationNewUser($email_id = false, $userId = false, $newUser = false) {
        if (!empty($email_id) && $email_id) {
            $session = StaticFunctions::getUserSession();
            $locationData = $session->getUserDetail('selected_location');
            $currentDate = $this->userCityTimeZone($locationData);
            $sl = StaticFunctions::getServiceLocator();
            $config = $sl->get('Config');
            $reservationModel = new UserReservation();
            $reservationStatus = isset($config ['constants'] ['reservation_status']) ? $config ['constants'] ['reservation_status'] : array();
            $upcomingCondition = array(
                'currentDate' => $currentDate,
                'status' => array(
                    $reservationStatus ['upcoming'],
                    $reservationStatus ['confirmed']
                ),
                'orderBy' => 'time_slot ASC'
            );
            $reservationDetail = $reservationModel->getReservationInvitationDetails($upcomingCondition);
            if (count($reservationDetail) > 0) {
                $userInvitationModel = new UserInvitation();
                $userInvitationModel->updateReservationData($userId, $email_id);
            }
        }
    }

    /**
     * Generates Unique Referral Code for a User
     * @author dhirendra
     * @param int $user_id
     * @return String Generated referral code
     */
    public static function generateUserReferralCode($user_id) {
        $tempRefCode = strtolower(substr(md5($user_id), 0, 6));
        $user_model = StaticFunctions::getServiceLocator()->get(User::class);
        if (!$user_model->hasRefCode($tempRefCode)) {
            return $tempRefCode;
        }
        return $tempRefCode . $user_id;
    }

    /**
     * To save new registered user's id and inviter_id data in user_referrals table.
     * @param int $user_id
     * @param String $referral_code
     * @return array
     */
    public function saveReferredUserInviterData($user_id, $referral_code) {
        $user = StaticFunctions::getServiceLocator()->get(User::class);
        $commonFunction = new \MCommons\CommonFunctions();
        $user_friends = StaticFunctions::getServiceLocator()->get(UserFriends::class);
        $userNotificationModel = StaticFunctions::getServiceLocator()->get(UserNotification::class);
        $current_user_data = $user->getUser(array('columns' => array('email', 'first_name'), 'where' => array('id' => $user_id)));
        
        //$userExists = $this->checkIfEmailExists($current_user_data['email']);
        $conn = $user->connectionObject();
        $conn->beginTransaction();
        try {
            $inviter_info = $user->getReferralCodeDetails($referral_code);
            $this->refferUserDetails = $inviter_info;
            $isFriend = $user_friends->isFriend($user_id, $inviter_info['id']); //1(both are friend) or 0

            $this->inviter_name = $inviter_info['first_name'];
            $ur = StaticFunctions::getServiceLocator()->get(UserReferrals::classs);
            $restaurantId = 0;
            if ($this->loyaltyCode) {
                $restaurantId = $this->restaurantId;
            } else {
                $restaurantId = 0;
            }
            // pr($ur->getInviterReferralExist($user_id, $restaurantId, $inviter_info['id']),1);
            if (empty($ur->getInviterReferralExist($user_id, $restaurantId, $inviter_info['id']))) {
                $ur->insert(array('user_id' => $user_id, 'inviter_id' => $inviter_info['id'], 'restaurant_id' => $restaurantId));
            }
            /* @var $cityModel \City\Model\City */
            $cityModel = StaticFunctions::getServiceLocator()->get(\City\Model\City::class);
            $session = StaticFunctions::getUserSession();
            $locationData = $session->getUserDetail('selected_location');    
            if(isset($locationData ['city_id']) && !empty($locationData ['city_id'])){
                $cityId = $locationData ['city_id'];
            }else{
                return false;
            }
            $cityDetails = $cityModel->cityDetails($cityId);
            $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                        'state_code' => $cityDetails [0] ['state_code']
            ));

            $currentDateTime = $cityDateTime->format('Y-m-d H:i:s');
            if ($isFriend == 0) {               
                 /* @var $userFriendsInvitation User\Model\UserFriendsInvitation */
                $userFriendsInvitation = StaticFunctions::getServiceLocator()->get(UserFriendsInvitation::class);
                $userFriendsInvitation->createReffUserInvitation(array(
                    'user_id' => $inviter_info['id'],
                    'invitation_status' => 1,
                    'email' => $current_user_data['email'],
                    'source' => 'munch',
                    'token' => $referral_code,
                    'created_on' => $currentDateTime,
                    'status' => 1,
                    'invitation_status' => 1,
                    'assignMuncher' => 0,
                    'cronUpdate' => 1
                ));

                //insert into user_invitations table

                $user_friends->insertFriends(array(
                    'user_id' => $user_id,
                    'friend_id' => $inviter_info['id'],
                    'invitation_id' => null,
                    'created_on' => $currentDateTime,
                    'status' => 1,
                ));
                $user_friends->insertFriends(array(
                    'user_id' => $inviter_info['id'],
                    'friend_id' => $user_id,
                    'invitation_id' => null,
                    'created_on' => $currentDateTime,
                    'status' => 1
                ));
            }

            if ($inviter_info['id']) {
                $message = "";
                $userPoints = $this->getAllocatedPoints("dinemorereferralinviter");

                if ($this->loyaltyCode && ucfirst($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
                    ##### inviter
                    $message = "Your friend " . $current_user_data['first_name'] . " joined you in " . $commonFunction->modifyRestaurantName($this->restaurant_name) . " Dine & More rewards program!";
                    $userPointMessage = "You earned " . $userPoints ['points'] . " points for following " . $inviter_info['first_name'] . "'s good advice and joining " . $commonFunction->modifyRestaurantName($this->restaurant_name) . " Dine & More rewards program!";


                    $feed = array(
                        'user_id' => $user_id,
                        'friend_id' => $inviter_info['id'],
                        'inviter_name' => ucfirst($current_user_data['first_name']),
                        'invitee_name' => ucfirst($inviteeName),
                        'invitee_id' => $inviter_info['id'],
                        'restaurant_name' => $commonFunction->modifyRestaurantName($this->restaurant_name),
                        'restaurant_id' => $restaurantId
                    );
                    $replacementData = array('inviter_name' => ucfirst($current_user_data['first_name']), 'restaurant_name' => $commonFunction->modifyRestaurantName($this->restaurant_name));
                    $otherReplacementData = array('restaurant_name' => $commonFunction->modifyRestaurantName($this->restaurant_name), 'inviter_name' => ucfirst($current_user_data['first_name']), 'invitee_name' => ucfirst($inviteeName));
                    $commonFunction->addActivityFeed($feed, 67, $replacementData, $otherReplacementData);


                    $cleverTap = array(
                        "user_id" => $inviter_info['id'],
                        "name" => $inviteeName,
                        "email" => $inviter_info['email'],
                        "identity" => $inviter_info['email'],
                        "currentDate" => $currentDateTime,
                        "reffered_email" => $current_user_data['email'],
                        "earned_points" => $userPoints ['points'],
                        "restaurant_name" => $this->restaurant_name,
                        "restaurant_id" => $restaurantId,
                        "eventname" => "refer_friend",
                        "refer_date" => $currentDateTime,
                        "event" => 1,
                        "is_register" => "yes"
                    );
                    $this->createQueue($cleverTap, 'clevertap');
                } else {
                    $message = "Your friend " . $current_user_data['first_name'] . " has joined you in your food adventures!";
                    $userPointMessage = "You earned " . $userPoints ['points'] . " points for following " . $inviter_info['first_name'] . "'s good advice and joining Munch Ado!";

                    $feed = array(
                        'user_id' => $user_id,
                        'friend_id' => $inviter_info['id'],
                        'inviter_name' => ucfirst($current_user_data['first_name']),
                        'invitee_name' => ucfirst($inviteeName),
                        'invitee_id' => $inviter_info['id']
                    );
                    $replacementData = array('inviter_name' => ucfirst($current_user_data['first_name']));
                    $otherReplacementData = array('inviter_name' => ucfirst($current_user_data['first_name']), 'invitee_name' => ucfirst($inviteeName));
                    $commonFunction->addActivityFeed($feed, 66, $replacementData, $otherReplacementData);

                    $cleverTap = array(
                        "user_id" => $inviter_info['id'],
                        "name" => $inviteeName,
                        "email" => $inviter_info['email'],
                        "identity" => $inviter_info['email'],
                        "currentDate" => $currentDateTime,
                        "reffered_email" => $current_user_data['email'],
                        "earned_points" => $userPoints ['points'],
                        "restaurant_name" => "MunchAdo",
                        "restaurant_id" => "",
                        "eventname" => "refer_friend",
                        "refer_date" => $currentDateTime,
                        "event" => 1,
                        "is_register" => "yes"
                    );
                    $this->createQueue($cleverTap, 'clevertap');
                }
                $this->referralPoint = $userPoints ['points'];
                //Give Point to Referal
                // User                               
                $this->giveReferralPoints($userPoints, $user_id, $userPointMessage);
                //Inviter
                $this->giveReferralPoints($userPoints, $inviter_info['id'], $message);



                $channelToUser = "mymunchado_" . $inviter_info['id'];
                $notificationArrayToUser = array(
                    "msg" => $message,
                    "channel" => $channelToUser,
                    "userId" => $inviter_info['id'],
                    "type" => 'registration',
                    "restaurantId" => $restaurantId,
                    'curDate' => $currentDateTime,
                    'user_name' => ucfirst($current_user_data['first_name']),
                    'registration_id' => $user_id,
                    'restaurant_name' => $commonFunction->modifyRestaurantName($this->restaurant_name),
                    'restaurant_id' => $restaurantId,
                    'is_live' => 1
                );

                $notificationJsonArrayToUser = array('user_id' => $inviter_info['id'], 'user_name' => ucfirst($current_user_data['first_name']), 'registration_id' => $user_id, 'restaurant_id' => $restaurantId, 'restaurant_name' => $commonFunction->modifyRestaurantName($this->restaurant_name));
                $userNotificationModel->createPubNubNotification($notificationArrayToUser, $notificationJsonArrayToUser);
                StaticFunctions::pubnubPushNotification($notificationArrayToUser);
                $this->restaurantId = $restaurantId;

                $this->first_name = ucfirst($current_user_data['first_name']);
                $this->email = $inviter_info['email'];
                $this->joinInviteeThenMailToInviter();
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            if (isset($_REQUEST['DeBuG'])) {
                throw $e;
            }
            return false;
        }
        return true;
    }

    public function restaurantPromotionEvent($promotionId, $currentDate) {
        $restaurantEvent = new \Restaurant\Model\RestaurantEvent();
        return $restaurantEvent->getRestaurantPromotionEvent($promotionId, $currentDate);
    }

    public function checkDomain($email_id) {
        $domainArray = explode('.', $email_id);
        $domainCount = count($domainArray);
        $domain = $domainArray[$domainCount - 1];
        return $domain;
    }

    /**
     * Used to get amount to be credited to inviter whose 3 invitees have placed order.
     * @param int $inviter_id
     * @return real
     */
    public function getThreeUsersCreditAmount($inviter_id) {

        return 30.0;
    }

    public function doReferralCreditSendMail($userId = false) {
        if ($userId) {
            $user = new User();
            $current_user_data = $user->getUser(array('columns' => array('email'), 'where' => array('id' => $userId)));
            $email = $current_user_data['email'];
            $template = "emailawarding";
            $layout = "default_emailer";
            $subject = "";
            $variables = array(
                'hostname' => ''
            );
            $subject = "Your Friends Made You $30";
            $emailData = array(
                'recievers' => $email,
                'variables' => $variables,
                'subject' => $subject,
                'template' => $template,
                'layout' => $layout
            );

            $this->emailSubscription($emailData);
        }
    }

    public function inviteFriends($userId, $userName, $currentDate, $email, $data, $baseUrl, $sender = array()) {
        $userModel = new User();
        $userFriendsInvitationModel = new UserFriendsInvitation();
        if ($userFriendsInvitationModel->getInvitatioExist($userId, $email)) {
            return array('success' => false);
        }
        $sl = StaticFunctions::getServiceLocator();
        $date = strtotime($currentDate);
        $expiredOn = strtotime("+7 day", $date);
        $expiredOn = date('Y-m-d H:i:s', $expiredOn);
        $userDetails = $userModel->getUserDetail(array(
            'column' => array(
                'first_name', 'last_name',
                'id'
            ),
            'where' => array(
                'email' => $email,
                'status' => 1
            )
        ));
        $user = '';
        $inDBFriend = false;
        if (!empty($userDetails) && $userDetails != null) {
            $user = $userDetails->getArrayCopy();
            $friendName = $user['first_name'] . ' ' . $user['last_name'];
            $inDBFriend = true;
        } else {
            $userEmail = explode("@", $email);
            $friendName = $userEmail[0];
            $inDBFriend = false;
        }
        /**
         * Send to Pubnub For All Friends
         */
        $userNotificationModel = new UserNotification();

        if ($inDBFriend == true) {
            $notificationMsg = ucfirst($userName) . ' would like to be friends with you and share in the common quest to find the best eats in town.';
            $channel = "mymunchado_" . $userDetails['id'];
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userDetails['id'],
                "type" => 'invite_friends',
                "restaurantId" => '',
                'curDate' => $currentDate, 'username' => ucfirst($userName)
            );
            $notificationJsonArray = array('username' => ucfirst($userName), "user_id" => $userDetails['id']);
            $response = $userNotificationModel->createPubNubNotification($notificationArray, $notificationJsonArray);
            $pubnub = StaticFunctions::pubnubPushNotification($notificationArray);
        }

        $insertdata = array(
            'user_id' => $userId,
            'email' => $email,
            'source' => 'munch',
            'created_on' => $currentDate,
            'token' => StaticFunctions::getUserSession()->token,
            'expired_on' => $expiredOn,
            'status' => '1'
        );
        $insertData = $userFriendsInvitationModel->createUserInvitation($insertdata);
        $mailText = $data;
        $config = $sl->get('Config');
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        // $acceptLink = $baseUrl . DS . 'wapi' . DS . 'user' . DS . 'accepted' . DS . $insertData . '?token=' . StaticFunctions::getUserSession()->token;
        $acceptLink = $webUrl . DS . 'friendInvitation' . DS . $insertData;
        $template = "friends-Invitation";
        $layout = 'email-layout/default_new';
        $variables = array(
            'username' => $userName,
            'friendname' => $friendName,
            'mailtext' => $mailText,
            'acceptlink' => $acceptLink,
            'hostname' => $webUrl
        );
        $subject = ucfirst($userName) . ' Gave Us Your Email.That’s Cool, Right?';

        // #################
        $emailData = array(
            'receiver' => array(
                $email
            ),
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout
        );

        //Send invitation mail
        //$this->sendMails($emailData,$sender);
        return array(
            'success' => true
        );
    }

    /**
     * Send 30$ email to inviter when 3 referred user's place order.
     * Update user's wallet and insert record in user_transactions table
     * @param string $inviter_id
     * @return boolean
     */
    public function doReferralCreditTransaction($inviter_id) {
        $ur = new \User\Model\UserReferrals();
        /* @var $connection \Zend\Db\Adapter\Driver\Pdo\Connection */
        $connection = $ur->getDbTable()->getWriteGateway()->getAdapter()->getDriver()->getConnection();
        try {
            $connection->beginTransaction();

            $ids = $ur->updateThreeUsersRefAmtCredited($inviter_id);
            $transaction_table_data = array(
                'user_id' => $inviter_id,
                'transaction_amount' => $this->getThreeUsersCreditAmount($inviter_id),
                'transaction_type' => 'credit',
                'category' => 2,
                'remark' => 'Credited against referred users ' . implode(',', $ids),
            );

            $ut = new \User\Model\UserTransactions();
            $ut->insertRecord($transaction_table_data);

            $user = new \User\Model\User();
            $user->updateUserWallet($transaction_table_data);
            $this->doReferralCreditSendMail($transaction_table_data['user_id']);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            \MUtility\MunchLogger::writeLog($e, 1, 'failed referral transaction for user_id=' . $inviter_id);
            return false;
        }
        return true;
    }

    public function isReferralCodeValid($referral_code, User $userdetail = null) {

        $record = $userdetail->getUserDetail(array(
            'columns' => array('id'),
            'where' => array('referral_code' => $referral_code)
        ));
        if (empty($record)) {
            throw new \Exception("We're sorry, this referral code is not valid, please try again.", 400);
        }
    }

    /**
     * Pubnub notification on 1st, 2nd, 3rd and so on referral credit 
     * @param int $user_id
     * @return boolean
     */
    public function sendReferralCreditPubnubNotification($user_id) {
        try {
            $userReferrals = new UserReferrals();
            $count = $userReferrals->getTotalReferredUsersWithAmountCredited($user_id);
            if ($count < 3) {
                throw new \Exception('Called for user_id ' . $user_id . ' with only ' . $count . ' ref_amt_credited = 1');
            }
            $cycles = intval($count / 3);
            if ($cycles == 1) {
                $notificationMsg = 'Your friends loved you enough to earn you $30';
            } else {
                $notificationMsg = 'Your friends loved you enough to earn you another $30';
            }
            $cityModel = new \Home\Model\City(); //18848
            $cityId = isset($selectedLocation ['city_id']) ? $selectedLocation ['city_id'] : 18848;
            $cityDetails = $cityModel->cityDetails($cityId);
            $cityDateTime = StaticFunctions::getRelativeCityDateTime(array(
                        'state_code' => $cityDetails [0] ['state_code']
            ));

            $currentDateTime = $cityDateTime->format('Y-m-d H:i:s');
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => "mymunchado_" . $user_id,
                "userId" => $user_id,
                "type" => 'invite_friends',
                "restaurantId" => '0',
                //"curDate" => $this->getUserCurrentDatetime($user_id),
                "curDate" => $currentDateTime
            );

            $userNotificationModel = new \User\Model\UserNotification();
            $userNotificationModel->createPubNubNotification($notificationArray, array('user_id' => $user_id));
            StaticFunctions::pubnubPushNotification($notificationArray);

            $userloginModel = new User();
            $options = array(
                'where' => array(
                    'id' => $user_id
                )
            );

            $userDetail = $userloginModel->getUserDetail($options);
            if ($userDetail) {

                $feed = array(
                    'user_id' => $user_id,
                    'user_name' => ucfirst($userDetail ['first_name'])
                );
                $replacementData = array('user_name' => ucfirst($userDetail ['first_name']));
                $otherReplacementData = array('user_name' => ucfirst($userDetail ['first_name']));
                ;

                $commonFunction = new \MCommons\CommonFunctions();
                if ($cycles == 1) {
                    $activityFeed = $commonFunction->addActivityFeed($feed, 63, $replacementData, $otherReplacementData);
                } else {
                    $activityFeed = $commonFunction->addActivityFeed($feed, 64, $replacementData, $otherReplacementData);
                }
            }
            return true;
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'failed pubnub notification of $30 credit user_id=' . $user_id);
            return false;
        }
    }

    public function reminderOrderMail($data) {
        $variables = $data;
        $recievers = array($data['email']);
        $template = 'preorder-user-mail';
        $subject = 'Planning Ahead We See';
        $layout = 'email-layout/default_new';
        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout
        );
        // #################
        // $this->sendMails($emailData);
    }

    /**
     * Get user's current date 'Y-m-d H:i:s' format 
     * @param int $user_id
     * @return string
     */
    public function getUserCurrentDatetime($user_id) {
        $user_timezone = 'America/New_York'; //get this from model
        $datetime = new \DateTime; // current time = server time
        $otherTZ = new \DateTimeZone($user_timezone);
        $datetime->setTimezone($otherTZ); // calculates with new TZ now
        return $datetime->format('Y-m-d H:i:s');
    }

    public function validateCardFromStripe($cardDetails) {

        $cust_id = NULL;
        $card_number = NUll;
        $stripeModel = new MStripe ();
        $useCardModel = new UserCard ();
        $userId = StaticFunctions::getUserSession()->getUserId();

        $locationData = StaticFunctions::getUserSession()->getUserDetail('selected_location');
        $currentDate = strtotime($this->userCityTimeZone($locationData));
        $currentMonth = date("n", $currentDate);
        $currentYear = date("Y", $currentDate);

        $uDetails = ($userId > 0) ? $useCardModel->fetchUserCard($userId) : array();

        $card_number = array();

        if (!empty($uDetails)) {
            foreach ($uDetails as $key => $val) {
                $date = explode('/', $val['expired_on']);
                $cardValidate = 0;
                if ($currentYear < $date[1]) {
                    $cardValidate = 1;
                } elseif ($currentYear == $date[1]) {
                    if ($date[0] >= $currentMonth) {
                        $cardValidate = 1;
                    } else {
                        $cardValidate = 0;
                    }
                } else {
                    $cardValidate = 0;
                }
                if ($cardValidate == 1) {
                    $cust_id = $val['stripe_token_id'];
                    $card_number[] = $val['card_number'];
                }
            }
        }

        //Add to card in strip and get token and card detail	
        $fourDigitofCardNo = substr($cardDetails['number'], -4);
        if (in_array($fourDigitofCardNo, $card_number)) {
            $add_card_response = $stripeModel->addCard($cardDetails, $cust_id);
        } else {
            $cust_id = NULL;
            $add_card_response = $stripeModel->addCard($cardDetails, $cust_id);
        }

        return $add_card_response ['response'];
    }

    public function formatLastLoginDate($start, $end, $type = null) {
        $timeshift = 'expired';
        if ($type == 'ago') {
            $type = 'ago';
            $timeshift = date('M d, Y', strtotime($end));
        } else {
            $type = 'later';
        }
        $sdate = strtotime($start);
        $edate = strtotime($end);
        $time = $sdate - $edate;

        if ($sdate > $edate) {
            if ($time >= 0 && $time <= 59) {
                // Seconds
                $timeshift = $time . ' seconds' . " " . $type;
            } elseif ($time >= 60 && $time <= 3599) {
                // Minutes
                $pmin = $time / 60;
                $premin = explode('.', $pmin);
                $timeshift = $premin [0] . ' min' . " " . $type;
            } elseif ($time >= 3600 && $time <= 86399) {
                // Hours
                $phour = $time / 3600;
                $prehour = explode('.', $phour);
                $timeshift = $prehour [0] . ' hrs' . " " . $type;
            } elseif ($time >= 86400) {
                // Days
                $pday = $time / 86400;
                $preday = explode('.', $pday);
                $timeshift = $preday [0] . ' days' . " " . $type;
                if ($preday [0] > 7) {
                    $timeshift = StaticFunctions::getFormattedDateTime($end, 'Y-m-d H:i:s', 'M d, Y');
                }
            }
        }
        return $timeshift;
    }

    public function handleInvalidToken($token) {
        $tokenModel = new \Auth\Model\Token();
        $tokenData = $this->findTokenDetail($token, $tokenModel);
        if ($tokenData) {
            $tokenModel->id = $tokenData[0]['id'];
        }
        $cityData = [
            'city_id' => '18848',
            'nbd_cities' => '',
            'latitude' => '40.7127',
            'longitude' => '-74.0059',
            'city_name' => 'New York',
            'locality' => 'New York',
            'is_browse_only' => '0',
            'state_name' => 'New York',
            'state_code' => 'NY',
        ];

        $tokenModel->token = $token;
        $tokenModel->ttl = 315360000;
        $tokenModel->created_at = date('Y-m-d H:i');
        $tokenModel->user_details = @serialize($cityData);
        $tokenModel->last_update_timestamp = time();
        $tokenModel->save();
    }

    private function findTokenDetail($token, $tokenModel) {
        $options = array(
            'columns' => array(
                'id',
                'user_details'
            ),
            'where' => array(
                'token' => $token
            ),
        );

        $tokenModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        return $tokenModel->find($options)->toArray();
    }

    public function getNewUserPromotion() {
        $count = count($this->userPromocodes);
        for ($i = 0; $i < $count; $i++) {
            $promocodeStartTimestamp = strtotime($this->userPromocodes[$i]['start_on']);
            $promocodeEndTimestamp = strtotime($this->userPromocodes[$i]['end_date']);
            if ($this->userPromocodes[$i]['promocodeType'] == 2 && $this->currentDateTimeUnixTimeStamp <= $promocodeEndTimestamp && $this->currentDateTimeUnixTimeStamp >= $promocodeStartTimestamp) {
                $this->promocodeId = $i;
                return true;
            }
        }
        return false;
    }

    public function getUserPromocode() {
        foreach ($this->userPromocodes as $key => $val) {
            $promocodeStartTimestamp = strtotime($this->userPromocodes[$key]['start_on']);
            $promocodeEndTimestamp = strtotime($this->userPromocodes[$key]['end_date']);
            if ($this->currentDateTimeUnixTimeStamp <= $promocodeEndTimestamp && $this->currentDateTimeUnixTimeStamp >= $promocodeStartTimestamp) {
                $this->promocodeId = $key;
                return true;
            }
        }
        return false;
    }

    public function getUserPromocodeDetails() {
        $userPromocodesModel = StaticFunctions::getServiceLocator()->get(UserPromoCodes::class);
        $joins [] = [
            'name' => ['p' => 'promocodes'],
            'on' => new \Zend\Db\Sql\Expression('p.id = promo_id AND p.status = 1'),
            'columns' => ['start_on', 'end_date', 'promocodeType', 'discount', 'discount_type', 'minimum_order_amount', 'slots', 'days', 'deal_for', 'title', 'description'],
            'type' => 'right'];
        $options = ['columns' => ['user_id', 'user_promocode_id' => 'id'], 'where' => ['reedemed' => 0, 'user_id' => $this->userId], 'order' => 'p.start_on', 'joins' => $joins];
        $this->userPromocodes = $userPromocodesModel->find($options)->toArray();
    }

    public function sweepstakesDuplicatImage($restaurantId, $currentDateTime, $type) {
        $sweepstakesCampaigns = new \Restaurant\Model\SweepstakesCampaigns();
        $sweepstakesCampaigns->rest_id = $restaurantId;
        $sweepstakesCampaigns->currentDateTime = $currentDateTime;
        $campaignsData = $sweepstakesCampaigns->findCampaigns();

        if ($sweepstakesCampaigns->campaignsStatus) {
            return $this->findDuplicateImage($type, $restaurantId, $campaignsData);
        } else {
            return 0;
        }
    }

    public function findDuplicateImage($type, $restaurantId, $campaignsData) {
        $cDImgStatus = $this->checkinDuplicateImage($restaurantId, $campaignsData);
        $rDImgStatus = $this->restaurantDuplicateImage($restaurantId, $campaignsData);

        if ($cDImgStatus == 3 || $rDImgStatus == 3) {
            $this->sendMailOnDuplicateImage();
            return 3;
        }
        return 0;
    }

    public function checkinDuplicateImage($restaurantId, $campaignsData) {
        $campaignsRestIds = preg_replace('/\s+/', '', $campaignsData[0]['rest_id']);
        $restIdArr = explode(",", $campaignsRestIds);
        if (in_array($restaurantId, $restIdArr)) {
            $checkinImage = new Model\CheckinImages();
            if ($checkinImage->findSweepstakesImage($this->userId, $restaurantId, $campaignsData) > 0) {
                return 3;
            }
        }
        return 0;
    }

    public function restaurantDuplicateImage($restaurantId, $campaignsData) {
        $campaignsRestIds = preg_replace('/\s+/', '', $campaignsData[0]['rest_id']);
        $restIdArr = explode(",", $campaignsRestIds);
        if (in_array($restaurantId, $restIdArr)) {
            $restaurantImage = new Model\UserRestaurantimage();
            if ($restaurantImage->findSweepstakesImage($this->userId, $restaurantId, $campaignsData) > 0) {
                return 3;
            }
        }
        return 0;
    }

    public function sendMailOnDuplicateImage() {
        $userModel = new User();
        $user_data = $userModel->getUserDetail(array(
            'columns' => array(
                'first_name',
                'last_name',
                'email'
            ),
            'where' => array(
                'id' => $this->userId
            )
        ));
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $sender = array();
        $variables = array('hostname' => PROTOCOL . $config['constants']['web_url']);
        $data = array('receiver' => $user_data['email'], 'subject' => 'You Did It! Again!', 'template' => 'you_did_it_again', 'layout' => 'email-layout/you_did_it_again', 'variables' => $variables);
        $this->sendMails($data, $sender);
    }

    public function checkExistingCodeWithUser() {
        $resServer = new Model\RestaurantServer();
        $resServer->user_id = StaticFunctions::getUserSession()->getUserId();
        $resServer->restaurant_id = $this->restaurantId;
        $resServer->code = $this->loyaltyCode;
        $isExistUserWithCode = $resServer->checkExistCodeWithUser($resServer->user_id, $resServer->restaurant_id);
        if ($isExistUserWithCode['0']['id'] == 0) {
            $userModel = new User();
            if ($resServer->user_id > 0) {
                $user_data = $userModel->getUserDetail(array(
                    'columns' => array('first_name', 'last_name', 'email'),
                    'where' => array('id' => $resServer->user_id)
                ));
                if (!empty($user_data) && strtoupper($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
                    $this->first_name = $user_data['first_name'];
                    $this->email = $user_data['email'];
                    if ($this->existReg == 1) {
                        $template = ($this->host_name == PROTOCOL . SITE_URL) ? "Welcome_To_Restaurant_Dine_More_Rewards_Exist_User" : "registration_from_micro_site_with_dine_more_code_exist_user"; //501N
                    } else {
                        $template = ($this->host_name == PROTOCOL . SITE_URL) ? "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password" : "registration_from_micro_site_with_dine_more_code"; //500N
                    }
                    $this->mailSmsRegistrationPassword($template);
                }
            }
            return true;
        }
        return false;
    }

    public function registerRestaurantServer() {
        $resServer = StaticFunctions::getServiceLocator()->get(RestaurantServer::class);
        $resServer->user_id = ($this->userId) ? $this->userId : StaticFunctions::getUserSession()->getUserId();
        $this->userId = $resServer->user_id;
        $resServer->restaurant_id = $this->restaurantId;
        $resServer->code = ucfirst($this->loyaltyCode);
        $resServer->status = 0;

        $locationData = StaticFunctions::getUserSession()->getUserDetail('selected_location', array());
        $currentDateTime = $this->userCityTimeZone($locationData);

        $resServer->date = $currentDateTime;

        if ($resServer->user_id > 0 && $this->isRegisterWithRestaurant($this->userId)) {
            if ($resServer->registerRestaurantServer()) {
                return true;
            }
        }
        return false;
    }

    public function parseLoyaltyRegistrationSms($string = false) {
        if ($string) {
            $regStr = array('REG', 'REGS', 'ENROLL');
            $string = urldecode($string);
            $req = explode(" ", $string);
            if (!isset($req['0']) || !isset($req['1']) || !isset($req['2'])) {
                if (isset($req['0'])) {
                    if (strtoupper(substr($req['0'], 0, 1)) == 'R') {
                        $this->loyaltyReg = "REG";
                    } elseif (strtoupper(substr($req['0'], 0, 1)) == 'E') {
                        $this->loyaltyReg = "ENROLL";
                    } else {
                        $this->loyaltyReg = "REG";
                    }
                } else {
                    $this->loyaltyReg = "REG";
                }

                return false;
            }
            $this->loyaltyReg = strtoupper($req['0']);
            if ($this->loyaltyReg === "ENROLL") {
                $this->email = $req['1'];
                $this->loyaltyCode = trim($req['2']);
                return true;
            }
            $this->email = $req['1'];
            $commonFunction = new CommonFunctions();
            if ($commonFunction->validateEmail($this->email)) {
                $this->loyaltyCode = trim($req['2']);
                if (in_array($this->loyaltyReg, $regStr)) {
                    return $this->parseLoyaltyCode($this->loyaltyCode);
                }
            }
        }
        return false;
    }

    public function parseLoyaltyCode($loyaltyCode, $restaurant_id = false) {
        $this->loyaltyCode = trim($loyaltyCode);
        $this->vendorNumber = substr($this->loyaltyCode, -2);
        $firstLetterRestaurantName = substr($this->loyaltyCode, 0, 1);
        $this->restaurantId = substr(substr($this->loyaltyCode, 0, -2), 1, strlen(substr($this->loyaltyCode, 0, -2)) - 1);
        if (!$this->isVendorNumber()) {
            return false;
        } elseif (ucfirst($this->loyaltyCode) == MUNCHADO_DINE_MORE_CODE) {
            $this->restaurant_name = "Munch Ado";
            $this->rest_code = "";
            return true;
        } else {
            if ($restaurant_id && $this->restaurantId != $restaurant_id) {
                return false;
            }

            $restaurantDetails = $this->restaurantTaged();
            if ($restaurantDetails && strtolower(substr($restaurantDetails->restaurant_name, 0, 1)) === strtolower($firstLetterRestaurantName)) {
                $this->restaurant_name = $restaurantDetails->restaurant_name;
                $this->rest_code = $restaurantDetails->rest_code;
                $this->restaurant_logo = $restaurantDetails->restaurant_logo_name;
                return true;
            }
        }
        return false;
    }

    public function userRegistrationWithSmsWeb() {
        $userloginModel = new User();
        $options = array(
            'where' => array(
                'email' => $this->email
            )
        );

        $userDetail = $userloginModel->getUserDetail($options);

        if (!empty($userDetail)) {
            $this->userId = $userDetail['id'];
            $this->first_name = $userDetail['first_name'];

            if (!$this->isRegisterWithRestaurant($this->userId)) {
                $this->isRegisterWithRestaurant = 1;
                return false;
            }

            StaticFunctions::getUserSession()->setUserId($userDetail['id']);
            $this->isRegisterUser = true;
            return true;
        }

        $sl = StaticFunctions::getServiceLocator();
        $userRegistration = $sl->get("User\Controller\WebUserDetailsController");
        $firstName = explode("@", $this->email);
        $data['email'] = $this->email;
        $data['password'] = trim($this->generate_verification_code());
        $this->smsRegistrationPassword = $data['password'];
        $data['captcha'] = "campaign";
        $data['first_name'] = $firstName[0];
        $data['accept_toc'] = 1;
        $data['user_source'] = ($this->loyaltyReg == 'REG' || $this->loyaltyReg == 'REGS') ? 'sms' : 'enrl';
        $userRegistration->restaurantName = $this->restaurant_name;
        $userRegistration->restaurantId = $this->restaurantId;
        return $userRegistration->create($data);
    }

    public function registrationLoyalityProgramCode($code) {
        if ($this->parseLoyaltyCode($code)) {
            return $this->registerRestaurantServer();
        } else {
            throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.");
        }
    }

    public function smsForRegisterUser($resquedata, $userSmsData) {
        $resServer = new \User\Model\RestaurantServer();
        //$userId = $session = $this->getUserSession()->getUserId();
        $resquedata['user_id'] = $this->userId;
        $resquedata['restaurant_id'] = $this->restaurantId;
        $resquedata['restaurant_name'] = $this->restaurant_name;
        $resquedata['loyalityCode'] = $this->loyaltyCode;
        $username = explode("@", $this->email);
        $isExistUserWithCode = $resServer->checkExistCodeWithUser($this->userId, $this->restaurantId);
        $commonFunctions = new CommonFunctions();
        $modifiedRestName = $commonFunctions->modifyRestaurantName($this->restaurant_name);
        if ($isExistUserWithCode['0']['id'] == 0) {
            $this->registerRestaurantServer();
            $sl = StaticFunctions::getServiceLocator();
            $config = $sl->get('Config');
            $webUrl = PROTOCOL . $config['constants']['web_url'];
            $urlRestName = str_replace(" ", "-", strtolower(trim($this->restaurant_name)));
            $restaurantUrl = $webUrl . "/restaurants/" . $urlRestName . "/" . $this->restaurantId . "/dine-more";
            $restaurantUrl = StaticFunctions::getBitlyShortUrl($restaurantUrl);
            $userSmsData['message'] = sprintf(SMS_REGISTER_USER, $modifiedRestName, $restaurantUrl);
            $resquedata['message'] = $userSmsData['message'];
            StaticFunctions::sendSmsClickaTell($userSmsData, 0);
            //$this->pushSmsRegistration($resquedata);
            //return array("success" => "Register User:".$userSmsData['message']);
        } else {

            $userSmsData['message'] = 'You are already a member of ' . $modifiedRestName . ' Dine & More program and have access to their exclusive specials!';
            $resquedata['message'] = $userSmsData['message'];
            StaticFunctions::sendSmsClickaTell($userSmsData, 0);
            //$this->pushSmsRegistration($resquedata);
            //return array("success" => $userSmsData['message']);
        }

        $this->dineAndMoreAwards("awardsregistration");
        $template = "Welcome_To_Restaurant_Dine_More_Rewards_Exist_User";
        $this->mailSmsRegistrationPassword($template);
        $clevertapData = array(
            "user_id" => $this->userId,
            "name" => $username[0],
            "email" => $this->email,
            "currentDate" => StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT),
            "source" => "sms",
            "loyalitycode" => $this->loyaltyCode,
            "restname" => $this->restaurant_name,
            "restid" => $this->restaurantId,
            "eventname" => "dine_and_more"
        );

        $this->clevertapUploadProfile($clevertapData);
        $this->clevertapRegistrationEvent($clevertapData);
        return array("success" => "Register User:" . $userSmsData['message']);
    }

    public function smsForNewUser($resquedata, $userSmsData) {
        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $username = explode("@", $this->email);
        $webUrl = PROTOCOL . $config['constants']['web_url'];
        $resquedata['user_id'] = $this->userId;
        $resquedata['restaurant_id'] = $this->restaurantId;
        $resquedata['restaurant_name'] = $this->restaurant_name;
        $resquedata['loyalityCode'] = $this->loyaltyCode;
        $this->registerRestaurantServer();
        $this->dineAndMoreAwards("awardsregistration");
        $feed = array(
            'user_id' => $this->userId,
            'user_name' => ucfirst($username[0]),
            'restaurant_name' => ucfirst($this->restaurant_name),
            'restaurant_id' => $this->restaurantId
        );
        $commonFunctions = new CommonFunctions();
        $commonFunctions->addActivityFeed($feed, 68, array('restaurant_name' => ucfirst($this->restaurant_name)), array());

        $modifiedRestName = $commonFunctions->modifyRestaurantName($this->restaurant_name);
        $urlRestName = str_replace(" ", "-", strtolower(trim($this->restaurant_name)));
        $restaurantUrl = $webUrl . "/restaurants/" . $urlRestName . "/" . $this->restaurantId . "/dine-more";
        $restaurantUrl = StaticFunctions::getBitlyShortUrl($restaurantUrl);
        $userSmsData['message'] = sprintf(SMS_NEW_USER, $modifiedRestName, $restaurantUrl, $this->restaurant_name);
        $resquedata['message'] = $userSmsData['message'];
        $template = 'Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password';
        $this->mailSmsRegistrationPassword($template);
        StaticFunctions::sendSmsClickaTell($userSmsData, 0);
        //$this->pushSmsRegistration($resquedata);
        $clevertapData = array(
            "user_id" => $this->userId,
            "name" => $username[0],
            "email" => $this->email,
            "currentDate" => StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT),
            "source" => "sms",
            "loyalitycode" => $this->loyaltyCode,
            "restname" => $this->restaurant_name,
            "restid" => $this->restaurantId,
            "eventname" => "dine_and_more"
        );


        $this->clevertapUploadProfile($clevertapData);
        $this->clevertapRegistrationEvent($clevertapData);

        return array("success" => "New User:" . $userSmsData['message']);
    }

    public function mailSmsRegistrationPassword($template) {

        if (ucfirst($this->loyaltyCode) == MUNCHADO_DINE_MORE_CODE) {
            return false;
        }
        $commonFunctions = new CommonFunctions();
        $modifiedRestName = $commonFunctions->modifyRestaurantName($this->restaurant_name);
        $layout = ($this->host_name == PROTOCOL . SITE_URL) ? 'default_dineandmore' : "ma_default";
        $subject = "Welcome to " . $modifiedRestName . " Dine & More Rewards!";
        $restnameForHeaderName = str_replace(" ", "-", $this->restaurant_name);
        $webUrl = $this->host_name;
        $password = ($this->smsRegistrationPassword) ? $this->smsRegistrationPassword : "";
        $first_name = ($this->first_name) ? $this->first_name : "";
        $variables = array(
            'web_url' => $webUrl,
            'first_name' => $first_name,
            'restnameForHeaderName' => $restnameForHeaderName,
            'restaurantId' => $this->restaurantId,
            'restaurantName' => $this->restaurant_name,
            'modifyRestName' => $modifiedRestName,
            'password' => $password,
            'restaurant_logo' => $this->restaurant_logo,
            'restaurant_address' => $this->restaurant_address,
            'facebook_url' => $this->facebook_url,
            'twitter_url' => $this->twitter_url,
            'instagram_url' => $this->instagram_url,
            'rererer_link' => $this->host_name,
        );
        $restname = preg_replace('/\s+/', '', $this->restaurant_name);
        $restname = preg_replace('/[^A-Za-z0-9\.]/', '', $restname);
        $sender = array('fromName' => $modifiedRestName . ' Dine & More', 'fromEmail' => $restname . '@Dine&More.MunchAdo.com');
        $mailData = array('recievers' => $this->email, 'template' => $template, 'layout' => $layout, 'variables' => $variables, 'subject' => $subject, 'sender' => $sender);

        //     pr($variables);
//       pr($mailData,1);
        $this->emailSubscription($mailData);
    }

    public function smsFaluar($resquedata, $userSmsData) {

        StaticFunctions::sendSmsClickaTell($userSmsData, 0);
        //$this->pushSmsRegistration($resquedata);
        return array("success" => "faluar:" . $userSmsData['message']);
    }

    public function pushSmsRegistration($data) {
        $config = StaticFunctions::getServiceLocator()->get('Config');
        if (isset($config['resque-service']) && $config['resque-service']) {
            $token = \Resque::enqueue($config['constants']['redis']['channel'], 'smsRegistration', $data, true);
            $status = new \Resque_Job_Status($token);
            return $status->get(); // Outputs the status
        }
    }

    public function isVendorNumber() {
        $venderNumberRange = range(00, 99);
        if (in_array($this->vendorNumber, $venderNumberRange)) {
            return true;
        }
        return false;
    }

    public function userTotalPoint($userId = false) {
        if ($userId) {
            $userPoints = StaticFunctions::getServiceLocator()->get(UserPoint::class);
            $totalPoints = $userPoints->countUserPoints($userId);
            $redeemPoint = $totalPoints[0]['redeemed_points'];
            $this->redeemPoint = $totalPoints[0]['redeemed_points'];
            return (int) ($totalPoints[0]['points'] - $redeemPoint);
        }

        return 0;
    }

    public function isRegisterWithRestaurant($userid = false) {
        $resServer = StaticFunctions::getServiceLocator()->get(RestaurantServer::class);
        $resServer->user_id = ($this->userId) ? $this->userId : $userid;
        $resServer->restaurant_id = $this->restaurantId;
        $resServerData = $resServer->isUserRegisterWithRestaurant();
        if ($resServerData['0']['id'] > 0) {
            return false;
        }
        return true;
    }

    public function isRegisterWithAnyRestaurant() {
        $resServer = StaticFunctions::getServiceLocator()->get(RestaurantServer::class);
        $resServer->user_id = $this->userId;
        $resServerData = $resServer->isUserRegisterWithAnyRestaurant();

        if ($resServerData['0']['id'] > 0) {
            $this->totalRegisterServer = $resServerData['0']['id'];
            return true;
        }
        return false;
    }

    public function getUserDealData($currentDate, $userId, $isMobile = false) {
        $on = "ud.deal_id = restaurant_deals_coupons.id and type != 'offer'";
        if ($isMobile) {
            $on = "ud.deal_id = restaurant_deals_coupons.id and (restaurant_deals_coupons.trend < 7 or restaurant_deals_coupons.trend is null) and type != 'offer'";
        }
        $currentDateTimeUnixTimeStamp = strtotime($currentDate);
        $dealCouponsModel = StaticFunctions::getServiceLocator()->get(RestaurantDealsCoupons::class);
        $joins = [];
        $joins [] = [
            'name' => [
                'ud' => 'user_deals'
            ],
            'on' => new \Zend\Db\Sql\Expression($on),
            'columns' => ['read'],
            'type' => 'inner'
        ];
        $dealOptions = [
            //'columns'=>array('id','restaurant_id','title','type','start_on','end_date','expired_on','deal_for','discount_type'),
            'where' => ['ud.user_id' => $userId, 'restaurant_deals_coupons.status' => 1, 'restaurant_deals_coupons.user_deals' => 1, 'ud.availed' => 0],
            'joins' => $joins,
            'order' => 'restaurant_deals_coupons.end_date DESC'
        ];
        $userDeal = $dealCouponsModel->find($dealOptions)->toArray();
        if (!empty($userDeal)) {
            $i = 0;
            foreach ($userDeal as $key => $val) {
                $userDeal[$key]['read'] = (int) $val['read'];
                $dealsEndDateTimeUnixTimeStamp = strtotime($val['end_date']);
                if ($currentDateTimeUnixTimeStamp <= $dealsEndDateTimeUnixTimeStamp) {
                    $this->resDealId[] = $val['restaurant_id'];
                    $this->getTotalUnreadDeal($userDeal[$key]['read']);
                    $this->userDeals[$i] = $userDeal[$key];
                    $this->userDeals[$i]['trend'] = ($userDeal[$key]['trend'] == NULL) ? 0 : (int) $userDeal[$key]['trend'];
                    $restaurantDetailModel = StaticFunctions::getServiceLocator()->get(Restaurant::class);
                    $resDetails = $restaurantDetailModel->findRestaurant(['where' => ['id' => $val['restaurant_id']]])->toArray();
                    $iPath = USER_REVIEW_IMAGE . strtolower($resDetails['rest_code']) . '/offer/';
                    $this->userDeals[$i]['offer_image'] = ($userDeal[$key]['image'] !== NULL) ? $iPath . $userDeal[$key]['image'] : '';
                    $this->userDeals[$i]['condition'] = "*offer made available via email after verification";
                    $i++;
                    continue;
                }
                unset($userDeal [$key]);
            }
        }
    }

    public function getOffer($currentDate, $userId) {

        $restaurantServer = new Model\RestaurantServer();
        $userDineRestaurant = $restaurantServer->userDineAndMoreRestaurant($userId);
        $userDineMoreRestaurant = count(array_keys($userDineRestaurant));

        $currentDateTimeUnixTimeStamp = strtotime($currentDate);
        $dealCouponsModel = new \Restaurant\Model\DealsCoupons();
        $dealOptions = array(
            'where' => array('status' => 1, 'type' => 'offer', 'restaurant_id' => 0),
            'order' => 'restaurant_deals_coupons.end_date DESC'
        );
        if ($userDineMoreRestaurant > 0) {
            $i = 0;
            foreach ($userDineRestaurant as $dkey => $dm) {
                $userDeal = $dealCouponsModel->find($dealOptions)->toArray();

                if (!empty($userDeal)) {

                    foreach ($userDeal as $key => $val) {
                        $userDeal[$key]['read'] = (int) $val['read'];
                        $dealsEndDateTimeUnixTimeStamp = strtotime($val['end_date']);
                        $dealsStartDateTimeUnixTimeStamp = strtotime($val['start_on']);
                        $dealsExpireDateTimeUnixTimeStamp = strtotime($val['expired_on']);
                        if ($currentDateTimeUnixTimeStamp <= $dealsEndDateTimeUnixTimeStamp) {
                            $this->offer[$i] = $userDeal[$key];
                            $this->offer[$i]['trend'] = ($userDeal[$key]['trend'] == NULL) ? 0 : (int) $userDeal[$key]['trend'];
                            $iPath = USER_REVIEW_IMAGE . 'offer/';
                            $this->offer[$i]['offer_image'] = $iPath . $val['image'];
                            $this->offer[$i]['condition'] = "";
                            $this->offer[$i]['restaurant_id'] = $dm['restaurant_id'];
                            $i++;
                        }

                        unset($userDeal [$key]);
                    }
                }
            }
        }
        // pr($this->offer,1);
    }

    public function getTotalUnreadDeal($read) {
        if ($read == 0) {
            $this->totalUnreadDeals = $this->totalUnreadDeals + 1;
        }
    }

    public function restaurantData($restaurant_id, $isMobile = false) {
        $restaurantModel = new Restaurant();
        $cuisine = new \Restaurant\Model\Cuisine();
        $cuisines = $cuisine->getRandRestaurantCuisineDetails(array('columns' => array('restaurant_id' => $restaurant_id)));
        $resCuisine = [];
        $resCuisineMob = '';
        if (!empty($cuisines)) {
            foreach ($cuisines as $k => $cuisine) {
                $resCuisine[$k] = $cuisine['cuisine'];
                if ($isMobile) {
                    $resCuisineMob .= $cuisine['cuisine'] . ", ";
                }
            }
        }

        $options = array(
            'columns' => array(
                'res_id' => 'id',
                'res_name' => 'restaurant_name',
                'res_code' => 'rest_code',
                'res_delivery' => 'delivery',
                'res_takeout' => 'takeout',
                'res_dining' => 'dining',
                'has_menu' => 'menu_available',
                'res_reservation' => 'reservations',
                'res_price' => 'price',
                'menu_without_price',
                'accept_cc_phone',
                'restaurant_image_name',
                'res_description' => 'description',
                'res_minimum_delivery' => 'minimum_delivery',
                'delivery_desc'
            ),
            'where' => array(
                'restaurants.id' => $restaurant_id,
                'restaurants.inactive' => 0,
                'restaurants.closed' => 0
            )
        );
        $restaurantModel->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $detailResponse = $restaurantModel->find($options)->toArray();
        if (!empty($detailResponse) && $isMobile) {
            $resCuisineMob = substr($resCuisineMob, 0, -2);
            $detailResponse[0]['res_cuisine'] = $resCuisineMob;
        } else {
            $detailResponse[0]['res_cuisine'] = $resCuisine;
        }
        //pr($detailResponse,1);
        return $detailResponse[0];
    }

    public function prepairUserDealAndRestaurantData($isMobile) {
        $restaurantData = [];
        //pr($this->offer,1);
        if (!empty($this->offer)) {
            foreach ($this->offer as $offerKey => $offerVal) {
                array_push($this->userDeals, $this->offer[$offerKey]);
            }
        }
        //pr($this->userDeals,1);
        if (!empty($this->userDeals)) {
            foreach ($this->userDeals as $dealKey => $dealVal) {
                if ($isMobile) {
                    if (!$this->userDeals[$dealKey]['menu_id'] && !$this->userDeals[$dealKey]['description']) {
                        if ($this->userDeals[$dealKey]['days']) {
                            $daysArr = explode(',', $this->userDeals[$dealKey]['days']);
                            $daysStr = "";
                            foreach ($daysArr as $keys => $val) {
                                $daysStr .= StaticFunctions::$dayMapping[$val] . ", ";
                            }
                            $days = substr($daysStr, 0, -2);
                        } else {
                            $days = "all days";
                        }

                        $this->userDeals[$dealKey]['description'] = "Get " . $this->userDeals[$dealKey]['title'] . ". The discount is automatically applied to your order total once it exceeds $" . $this->userDeals[$dealKey]['minimum_order_amount'] . ". Offer valid from " . date("M d, Y h:m A", strtotime($this->userDeals[$dealKey]['start_on'])) . " on " . $days . " (check restaurant’s open hours) only for online orders on Munch Ado.";
                    }

                    $restaurantData[] = $this->restaurantData($dealVal['restaurant_id'], $isMobile);
                    $restaurantData[$dealKey]['res_delivery'] = (int) $restaurantData[$dealKey]['res_delivery'];
                    $restaurantData[$dealKey]['res_takeout'] = (int) $restaurantData[$dealKey]['res_takeout'];
                    $restaurantData[$dealKey]['res_dining'] = (int) $restaurantData[$dealKey]['res_dining'];
                    $restaurantData[$dealKey]['has_menu'] = (int) $restaurantData[$dealKey]['has_menu'];
                    $restaurantData[$dealKey]['res_reservations'] = (int) $restaurantData[$dealKey]['res_reservation'];
                    $restaurantData[$dealKey]['menu_without_price'] = (int) $restaurantData[$dealKey]['menu_without_price'];
                    $restaurantData[$dealKey]['accept_cc_phone'] = (int) $restaurantData[$dealKey]['accept_cc_phone'];
                    $restaurantData[$dealKey]['res_primary_image'] = $restaurantData[$dealKey]['restaurant_image_name'];
                    $restaurantData[$dealKey]['ordering_enabled'] = ($restaurantData[$dealKey]['accept_cc_phone'] && ($restaurantData[$dealKey]['res_delivery'] || $restaurantData[$dealKey]['res_takeout'] || $restaurantData[$dealKey]['res_reservations']) && $restaurantData[$dealKey]['menu_without_price'] == 0) ? 1 : 0;
                    $restaurantData[$dealKey]['oh_ft'] = $this->getRestaurantOhFt($dealVal['restaurant_id']);
                    $restaurantData[$dealKey]['deal'][] = $this->userDeals[$dealKey];
                } else {
                    $restaurantData = $this->restaurantData($dealVal['restaurant_id']);
                    $this->userDeals[$dealKey]['res_name'] = $restaurantData['res_name'];
                    $this->userDeals[$dealKey]['res_code'] = $restaurantData['res_code'];
                    $this->userDeals[$dealKey]['res_price'] = $restaurantData['res_price'];
                    $this->userDeals[$dealKey]['res_delivery'] = $restaurantData['res_delivery'];
                    $this->userDeals[$dealKey]['res_takeout'] = $restaurantData['res_takeout'];
                    $this->userDeals[$dealKey]['res_dining'] = $restaurantData['res_dining'];
                    $this->userDeals[$dealKey]['has_menu'] = $restaurantData['has_menu'];
                    $this->userDeals[$dealKey]['res_reservation'] = $restaurantData['res_reservation'];
                    $this->userDeals[$dealKey]['menu_without_price'] = $restaurantData['menu_without_price'];
                    $this->userDeals[$dealKey]['accept_cc_phone'] = $restaurantData['accept_cc_phone'];
                    $this->userDeals[$dealKey]['restaurant_image_name'] = $restaurantData['restaurant_image_name'];
                    $this->userDeals[$dealKey]['res_cuisine'] = $restaurantData['res_cuisine'];
                }
            }
        }

        if ($isMobile) {
            return $restaurantData;
        }
        //$this->userDeals
        return $this->userDeals;
    }

    public function getRestaurantOhFt($restId) {
        $restaurantFunctions = new \Restaurant\RestaurantDetailsFunctions();
        $restaurantOhFt = $restaurantFunctions->getRestaurantDisplayTimings($restId);
        $oh_ft = "";
        if (!empty($restaurantOhFt)) {
            foreach ($restaurantOhFt as $key => $val) {
                if ($val['operation_hours']) {
                    $oh_ft .= $val['calendar_day'] . "|" . $val['operation_hours'] . "$";
                }
            }
            $oh_ft = substr($oh_ft, 0, -1);
        }
        return $oh_ft;
    }

    public function dineAndMoreAwards($awardType, $options = false) {
        $points = [];
        if (!$this->isRegisterWithRestaurant($this->userId)) {
            switch ($awardType) {
                case "awardsregistration":
                    $type = "dine_more";
                    $this->typeKey = 'registration_id';
                    $this->typeValue = $this->userId;
                    $points = $this->getAllocatedPoints($awardType);
                    $commonFunctions = new CommonFunctions();
                    $restaurantName = $commonFunctions->modifyRestaurantName($this->restaurant_name);
                    $nMessage = "";

                    if (!$this->isRegisterUser && ucfirst($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
                        $message = "You joined " . $restaurantName . " Dine & More rewards program!";
                        $nMessage = "Thank you for joining " . $restaurantName . " Dine & More rewards program, presented by Munch Ado!";
                        $this->dineAndMoreNotification($nMessage, $type);
                    } else {
                        $message = "You joined " . $restaurantName . " Dine & More rewards program!";
                        //$nMessage = "Thank you for joining ".$restaurantName." Dine & More rewards program!";  //bug id 38875 neela                  
                    }

                    if (ucfirst($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
                        $this->givePoints($points, $this->userId, $message);
                    }
                    break;
                case "awardsreferingfriend":
                    $points = $this->getAllocatedPoints($awardType);
                    $message = "Dine & more awards you " . $points['points'] . " points for referring a friend";
                    if ($this->referral) {
                        $points['message'] = $message;
                        return $points;
                    }
                    if ($this->referralJoin) {
                        $this->givePoints($points, $this->userId, $message);
                    }
                    break;
                case "order":
                    $points = $this->awardPointForOrder();
                    return $points;
                    break;
                case "awardsreservation":
                    $points = $this->getAllocatedPoints($awardType);
                    $message = "You have upcoming plans! This calls for a celebration, here are " . $points ['points'] . " points!";
                    if ($this->earlyBirdSpecial()) {
                        $message = "Bonus points for you at " . $this->restaurant_name . " for making a reservation during your first 30 days with Dine & More";
                        $points ['points'] = $points ['points'] + 100;
                    }
                    $points['message'] = $message;
                    return $points;
                    break;
                case "awardsreview":
                    $type = "reviews";
                    $message = "";
                    $points = $this->getAllocatedPoints($awardType);

                    if ($this->earlyBirdSpecial()) {
                        $points ['points'] = $points ['points'] + 100;
                        $message = "Bonus points for you at " . $this->restaurant_name . " for writing a review during your first 30 days with Dine & More";
                    }

                    $points['message'] = $message;

                    return $points;
                    break;
                case "awardsuploadpic":
                    $points = $this->getAllocatedPoints("imageApproved");
                    $type = "upload photo";
                    //if ($this->imageUploadCount > 1) {
                    $points['points'] = 25;
                    //}
                    $message = "On a scale from 0-10 points, we give this photo: " . $points['points'] . " points. Way to go!";
                    $points['message'] = $message;
                    return $points;
                    break;
            }
        }

        return $points;
    }

    public function earlyBirdSpecial() {
        $restaurantServer = new Model\RestaurantServer();
        $restaurantServer->user_id = $this->userId;
        $restaurantServer->restaurant_id = $this->restaurantId;
        $restServerDetails = $restaurantServer->findExistingUser();

        if ($this->activityDate && !empty($restServerDetails)) {
            $registrationDate = $restServerDetails[0]['date'];
            $date1 = new \DateTime($registrationDate);
            $date2 = new \DateTime($this->activityDate);
            $interval = $date1->diff($date2);
            //pr($interval->days,1);
            if ($interval->days <= EARLY_BIRD_SPECIAL_DAYS) {
                return true;
            }
        }
        return false;
    }

    public function awardPointForOrder() {
        $points = [];
        $message = '';
        $type = 'order';
        if ($this->order_amount >= 50) {
            $point = 100 + (2 * $this->order_amount);
            $message = "You earned " . floor($point) . " points with your delivery order from " . $this->restaurant_name . "!";
            if ($this->earlyBirdSpecial()) {
                $message = "Bonus points for you at " . $this->restaurant_name . " for placing an order during your first 30 days with Dine & More";
                $point = $point + 100;
            }
        } else {
            $point = 2 * $this->order_amount;
            $message = "You earned " . floor($point) . " points with your delivery order from " . $this->restaurant_name . "!";
        }
        if ($this->total_order == 0) {
            $points = $this->getAllocatedPoints("awardsfirstorder");
            $point = $points['points'] + $point;
            $message = "You earned " . floor($point) . " points with your " . $this->orderType . " order from " . $this->restaurant_name . "!";
        }

        $point = floor($point);
        $points['message'] = $message;
        $points['points'] = $point;
        return $points;
    }

    public function dineAndMoreNotification($message, $type) {
        $channelToUser = "mymunchado_" . $this->userId;
        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $this->userCityTimeZone($locationData);

        $notificationArrayToUser = array(
            "msg" => $message,
            "channel" => $channelToUser,
            "userId" => $this->userId,
            "type" => $type,
            "restaurantId" => $this->restaurantId,
            'curDate' => $currentDate,
            'restaurant_name' => ucfirst($this->restaurant_name),
            $this->typeKey => $this->typeValue,
            'is_live' => 1
        );
        $userNotificationModel = StaticFunctions::getServiceLocator()->get(UserNotification::class);
        $notificationJsonArrayToUser = array('user_id' => $this->userId, $this->typeKey => $this->typeValue, 'restaurant_id' => $this->restaurantId, 'restaurant_name' => ucfirst($this->restaurant_name));
        return true;
        $userNotificationModel->createPubNubNotification($notificationArrayToUser, $notificationJsonArrayToUser);
        StaticFunctions::pubnubPushNotification($notificationArrayToUser);


        $channelDashboard = "dashboard_" . $this->restaurantId;
        $notificationMsg = "You have a new Dine & More member!";
        $notificationArrayToUser = array(
            "msg" => $notificationMsg,
            "channel" => $channelDashboard,
            "userId" => $this->userId,
            "type" => $type,
            "restaurantId" => $this->restaurantId,
            'curDate' => StaticFunctions::getRelativeCityDateTime(array(
                'restaurant_id' => $this->restaurantId
            ))->format(StaticFunctions::MYSQL_DATE_FORMAT),
            'restaurant_name' => ucfirst($this->restaurant_name),
            $this->typeKey => $this->typeValue,
            'is_live' => 1
        );
        $notificationJsonArrayToUser = array('user_id' => $this->userId, $this->typeKey => $this->typeValue, 'restaurant_id' => $this->restaurantId, 'restaurant_name' => ucfirst($this->restaurant_name));
        $userNotificationModel->createPubNubNotification($notificationArrayToUser, $notificationJsonArrayToUser);
        StaticFunctions::pubnubPushNotification($notificationArrayToUser);
    }

    public function createQueue($data, $class) {
        StaticFunctions::resquePush($data, $class);
    }

    public function giveReferralPoints($points, $userId, $message = null, $refId = null) {
        $currentDate = '';
        if ($this->restaurantId) {
            $currentDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $this->restaurantId
                    ))->format(StaticFunctions::MYSQL_DATE_FORMAT);
        }

        $userPointsModel = new UserPoint ();
        $data = array(
            'user_id' => $userId,
            'point_source' => $points ['id'],
            'points' => $points ['points'],
            'created_at' => $currentDate,
            'status' => 1,
            'points_descriptions' => $message,
            'ref_id' => $refId,
            'restaurant_id' => $this->restaurantId
        );
        $userPointsModel->createPointDetail($data);
        $userModel = new User ();
        $currentPoints = $userModel->countUserPoints($userId);
        if (!empty($currentPoints)) {
            $totalPoints = $currentPoints [0] ['points'] + $points ['points'];
        } else {
            $totalPoints = $points ['points'];
        }
        $userModel->updateUserPoint($userId, $totalPoints);
    }

    public function sendServerRegistrationEmail($data) {
        $recievers = array(
            $data ['recievers']
        );
        $template = $data ['template'];
        $layout = $data ['layout'];
        $variables = $data ['variables'];
        $subject = 'Welcome to the Dine & More Server Appreciation Program!';
        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout
        );

        $this->sendServerRegisterMails($emailData);
    }

    public function sendServerRegisterMails($data, $sender = array()) {
        if (empty($sender)) {
            $senderEmail = 'notifications@munchado.com';
            $senderName = "Munch Ado";
        } else {
            $senderEmail = 'notifications@munchado.com'; //isset($sender['email']) ? $sender['email'] : '';
            $senderName = isset($sender['first_name']) ? $sender['first_name'] : '';
        }
        $recievers = array(
            $data ['receiver']
        );
        $template = "email-template/" . $data ['template'];
        $layout = (isset($data ['layout'])) ? $data ['layout'] : 'email-layout/default';
        StaticFunctions::sendMail($senderEmail, $senderName, $recievers, $template, $layout, $data ['variables'], $data ['subject']);
    }

    public function restaurantTaged($restaurantId = false) {
        if ($restaurantId) {
            $this->restaurantId = $restaurantId;
        }
        $tags = StaticFunctions::getServiceLocator()->get(Tags::class);
        $tagsDetails = $tags->getTagDetailByName("dine-more");
        if (!empty($tagsDetails)) {
            $restaurant = StaticFunctions::getServiceLocator()->get(Restaurant::class);
            $joins [] = [
                'name' => [
                    'rt' => 'restaurant_tags'
                ],
                'on' => 'rt.restaurant_id = restaurants.id',
                'columns' => [
                    'tag_id',
                ],
                'type' => 'inner'
            ];
            return $restaurant->findByRestaurantId(
                            [
                                'columns' => ['restaurant_name', 'rest_code', 'restaurant_logo_name'],
                                'where' => ['restaurants.id' => $this->restaurantId, 'rt.tag_id' => $tagsDetails[0]['tags_id'], 'rt.status' => 1],
                                'joins' => $joins
                            ]
            );
        }
    }

    public function serverRequestToUserRegistration() {
        $userloginModel = new User();
        $options = array(
            'where' => array(
                'email' => $this->email
            )
        );

        $userDetail = $userloginModel->getUserDetail($options);

        if (!empty($userDetail)) {
            $this->userId = $userDetail['id'];
            $this->first_name = $userDetail['first_name'];

            if ($this->parseLoyaltyCode($this->loyaltyCode)) {
                if ($this->registerRestaurantServer()) {
                    $this->dineAndMoreAwards("awardsregistration");
                    $this->isRegisterWithRestaurant = 1;
                    $this->requestByServerForNewUserRegistration = 0;
                    return true;
                } else {
                    $this->isRegisterWithRestaurant = 0;
                    $this->requestByServerForNewUserRegistration = 0;
                    return true;
                }
            } else {
                return false;
            }
        } else {
            $password = trim($this->generate_verification_code());
            $emailArray = explode("@", $this->email);
            $data['first_name'] = $emailArray[0];
            $data['email'] = $this->email;
            $data['password'] = $password;
            $this->smsRegistrationPassword = $password;
            $data['accept_toc'] = 1;
            $data['source'] = 'srv';
            $data['loyality_code'] = $this->loyaltyCode;
            $data ['newsletter_subscription'] = 1;
            $data ['user_source'] = "sms";

            return $this->registerUserWithServer($data);
        }
    }

    public function registerUserWithServer($data) {

        $userloginModel = new User ();
        $userAccountModel = new UserAccount();

        $userNotificationModel = new \User\Model\UserNotification();
        if ($this->parseLoyaltyCode($data['loyality_code'])) {
            $currentDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $this->restaurantId
                    ))->format(StaticFunctions::MYSQL_DATE_FORMAT);
            $city_id = StaticFunctions::$city_id;
            $sl = StaticFunctions::getServiceLocator();
            $config = $sl->get('Config');

            $webUrl = PROTOCOL . $config['constants']['web_url'];
            $userloginModel->first_name = $data['first_name'];
            $userloginModel->email = $data['email'];
            $userloginModel->password = md5($data['password']);
            $userloginModel->accept_toc = $data['accept_toc'];
            $userloginModel->last_name = (isset($data ['last_name'])) ? $data ['last_name'] : '';
            $userloginModel->newsletter_subscribtion = (isset($data ['newsletter_subscription'])) ? $data ['newsletter_subscription'] : '';
            $userloginModel->created_at = $currentDate;
            $userloginModel->update_at = $currentDate;
            $userloginModel->last_login = $currentDate;
            $userloginModel->order_msg_status = '';
            $userloginModel->status = 1;
            $userloginModel->bp_status = 0;
            $userloginModel->display_pic_url = 'noimage.jpg';
            $userloginModel->display_pic_url_large = 'noimage.jpg';
            $userloginModel->display_pic_url_normal = 'noimage.jpg';
            $userloginModel->city_id = $city_id;

            $userloginModel->userRegistration();

            $userAccountModel->user_source = isset($data['source']) ? $data['source'] : 'iOS';
            $userAccountModel->user_id = $userloginModel->id;
            $userAccountModel->display_pic_url = "noimage.jpg";
            $userAccountModel->display_pic_url_large = 'noimage.jpg';
            $userAccountModel->display_pic_url_normal = 'noimage.jpg';
            $userAccountModel->first_name = $data['first_name'];

            $userAccountModel->userAccountRegistration();

            $points = $this->getAllocatedPoints('normalRegister');
            $points['type'] = "normalRegister";
            $message = "All life is a game. Here are 100 points to get you ahead of the game. Don't worry, it's not cheating.";
            $this->givePoints($points, $userloginModel->id, $message);

            $notificationMsg = 'Welcome to Munch Ado! From now on, we’ll be helping you get from hangry to satisfied.';
            $channel = "mymunchado_" . $userloginModel->id;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userloginModel->id,
                "type" => 'registration',
                "restaurantId" => '0',
                'curDate' => $currentDate
            );

            $userNotificationModel->createPubNubNotification($notificationArray);
            StaticFunctions::pubnubPushNotification($notificationArray);


            $template = 'user-registration';
            $layout = 'email-layout/default_register';
            $variables = array(
                'username' => $userloginModel->first_name,
                'hostname' => $webUrl
            );
            $mailData = array('recievers' => $userloginModel->email, 'layout' => $layout, 'template' => $template, 'variables' => $variables);
            $this->sendRegistrationEmail($mailData);

            $this->createSettings($userloginModel->id, $userloginModel->newsletter_subscribtion);

            //Register Restaurant Server
            $this->userId = $userloginModel->id;
            $this->registerRestaurantServer();
            $this->first_name = $userloginModel->first_name;
            $this->email = $userloginModel->email;
            $this->dineAndMoreAwards("awardsregistration");

            $this->mailSmsRegistrationPassword("Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password");



            ############## salesmanago Event ##################
            $salesData = [];
            $salesData['name'] = $userloginModel->first_name;
            $salesData['email'] = $userloginModel->email;
            $salesData['dine_more'] = ($this->loyaltyCode) ? "Yes" : "No";
            $salesData['sms_user'] = (isset($data['user_source']) && $data['user_source'] == 'sms') ? "Yes" : "No";
            $salesData['owner_email'] = 'no-reply@munchado.com';
            $salesData['restaurant_name'] = ($this->loyaltyCode) ? $this->restaurant_name : "";
            $salesData ['restaurant_id'] = ($this->loyaltyCode) ? $this->restaurantId : "";
            $salesData['tags'] = ($this->loyaltyCode) ? array("Registration_form", "Dine_and_More") : array("Registration_form");
            $salesData['contact_ext_event_type'] = "OTHER";
            $salesData['redeempoint'] = 0;

            $salesData['point'] = 200;
            $salesData['totalpoint'] = (int) $this->userTotalPoint($userloginModel->id);

            $salesData['identifier'] = "register";
            $salesData['user_source'] = (isset($data['user_source']) && $data['user_source'] != '') ? $data['user_source'] : "";
            $salesData['password'] = $data['password'];

            //$this->createQueue($salesData, 'Salesmanago');
            ###################################################
            //pr($userloginModel->email);
            $this->isRegisterWithRestaurant = 2;
            $this->requestByServerForNewUserRegistration = 1;
            return true;
        } else {
            return false;
        }
    }

    public function dineAndMoreAwardsLogin($awardType, $isRegisterWithAnyRestaurant = false, $restaurantId = false) {

        $nMessage = "";

        $commonFunctions = new CommonFunctions();
        $restaurantName = $commonFunctions->modifyRestaurantName($this->restaurant_name);
        if ($isRegisterWithAnyRestaurant) { // not register with any restaurant
            $nMessage = "You joined " . $restaurantName . " Dine & More rewards program!";
        } elseif ($restaurantId == '') {
            $nMessage = "Thank you for joining " . $restaurantName . " Dine & More rewards program, presented by Munch Ado!";
        } else {
            $nMessage = "Thank you for joining " . $restaurantName . " Dine & More program!";
        }
        $type = "dine_more";
        $points = $this->getAllocatedPoints($awardType);

        if (ucfirst($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
            $message = "You joined " . $restaurantName . " Dine & More rewards program!";
            $this->dineAndMoreNotification($nMessage, $type);
            $this->givePoints($points, $this->userId, $message);
        }
    }

    public function existUserJoinDineMoreByReferral($data, $userid = false) {

        $loyalityCode = $data['loyality_code'];
        $referralCode = $data['referral_code'];

        if (ucfirst($loyalityCode) === MUNCHADO_DINE_MORE_CODE) {
            return true;
        }

        if (!$this->parseLoyaltyCode($loyalityCode)) {
            throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.", 400);
        }

        if (!$this->isRegisterWithRestaurant($userid)) {
            return true;
        }

        ######### Intigration of user reffer invitation ############
        if ($referralCode) {
            $this->saveReferredUserInviterData($data['user_id'], $referralCode);
        }
        ############################################################

        $this->userId = $data['user_id'];
        $this->first_name = $data['first_name'];
        $this->email = $data['email'];

        $isRegisterWithAnyRestaurant = $this->isRegisterWithAnyRestaurant();

        $template = $this->getTemplate($isRegisterWithAnyRestaurant);

        $this->registerRestaurantServer();

        $this->dineAndMoreAwardsLogin("awardsregistration", $isRegisterWithAnyRestaurant);

        $this->mailSmsRegistrationPassword($template);

        return true;
    }

    public function getTemplate($isRegisterWithAnyRestaurant) {
        if ($isRegisterWithAnyRestaurant) { //Not Register with any restaurant            
            $template = "Welcome_To_Restaurant_Dine_More_Rewards_Exist_User"; //501N
        } else {
            $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
        }
        return $template;
    }

    public function joinInviteeThenMailToInviter() {
        $commonFunction = new CommonFunctions();
        $first_name = ($this->first_name) ? $this->first_name : "";

        $modifiedRestName = $commonFunction->modifyRestaurantName($this->restaurant_name);

        if (ucfirst($this->loyaltyCode) == MUNCHADO_DINE_MORE_CODE) {
            $subject = "Your Friend Has Joined Munch Ado!";
            $modifiedRestName = "Munch Ado";
        } else {
            $subject = "Your Friend Joined " . $modifiedRestName . " Dine & More Program! ";
        }
        $template = "To_Go_For_30_From_Munch_Ado";
        $layout = "default_dineandmore";

        $sl = StaticFunctions::getServiceLocator();
        $config = $sl->get('Config');
        $restnameForHeaderName = str_replace(" ", "-", $this->restaurant_name);
        $webUrl = PROTOCOL . $config['constants']['web_url'];


        $variables = array(
            'web_url' => $webUrl,
            'inviteename' => $first_name,
            'restnameForHeaderName' => $restnameForHeaderName,
            'restaurantId' => $this->restaurantId,
            'restaurantName' => $this->restaurant_name,
            'modifyRestName' => $modifiedRestName,
            'point15' => 15,
            'point250' => 250,
            'loyalityCode' => $this->loyaltyCode);
        $restname = preg_replace('/\s+/', '', $this->restaurant_name);
        $restname = preg_replace('/[^A-Za-z0-9\.]/', '', $restname);
        $sender = array('fromName' => $modifiedRestName . ' Dine & More', 'fromEmail' => $restname . '@Dine&More.MunchAdo.com');
        $mailData = array('recievers' => $this->email, 'template' => $template, 'layout' => $layout, 'variables' => $variables, 'subject' => $subject, 'sender' => $sender);
        $this->emailSubscription($mailData);
    }

    public function registerUserInBulk($data) {
        try {
            $userDetail = array();
            $userloginModel = new User ();
            $userAccountModel = new UserAccount();
            if (!isset($data ['first_name']) || empty($data ['first_name'])) {
                throw new \Exception("First name can not be empty.", 400);
            } else {
                $userloginModel->first_name = $data ['first_name'];
            }
            if (!isset($data ['email']) || empty($data ['email'])) {
                throw new \Exception("Email can not be empty.", 400);
            } else {
                $userloginModel->email = $data ['email'];
            }

            if (!isset($data ['password']) || empty($data ['password'])) {
                throw new \Exception("Password can not be empty.", 400);
            } else {
                $userloginModel->password = md5($data ['password']);
            }

            if (!isset($data ['accept_toc'])) {
                throw new \Exception("Required to accept term & condition.", 400);
            }


            $loyalityCode = (isset($data['loyality_code']) && !empty($data['loyality_code'])) ? $data['loyality_code'] : "";

            ############## Loyality Program Registration code validation #############
            if ($loyalityCode) {
                if (!$this->parseLoyaltyCode($loyalityCode)) {
                    throw new \Exception("Sorry we could not detect a valid code. Re-enter and try again.", 400);
                }
            }
            ##########################################################################

            $options = array(
                'where' => array(
                    'email' => $userloginModel->email
                )
            );

            $userDetail = $userloginModel->getUserDetail($options);
            if (!empty($userDetail)) {
                throw new \Exception("Email is already registered.", 400);
            }

            unset($data['token']);
            $userNotificationModel = new \User\Model\UserNotification();
            $session = StaticFunctions::getUserSession();
            $locationData = array(
                "city_id" => 18848,
                "nbd_cities" => "",
                "latitude" => "40.7127",
                "longitude" => "-74.0059",
                "city_name" => "New York",
                "locality" => "New York",
                "is_browse_only" => 0,
                "state_name" => "New York",
                "state_code" => "NY",
                "country_code" => "US",
            );
            $cityId = 18848; //isset($locationData ['city_id']) ? $locationData ['city_id'] : "";
            $userloginModel->city_id = $cityId;
            $currentDate = $this->userCityTimeZone($locationData);
            $sl = StaticFunctions::getServiceLocator();
            $config = $sl->get('Config');
            $webUrl = PROTOCOL . $config['constants']['web_url'];

            $userloginModel->last_name = (isset($data ['last_name'])) ? $data ['last_name'] : '';
            $userloginModel->newsletter_subscribtion = (isset($data ['newsletter_subscription'])) ? $data ['newsletter_subscription'] : '';
            $userloginModel->created_at = $currentDate;
            $userloginModel->update_at = $currentDate;
            $userloginModel->last_login = $currentDate;
            $userloginModel->order_msg_status = '';
            $userloginModel->status = 1;
            $userloginModel->bp_status = 0;
            $response1 = $userloginModel->userRegistration();

            if (!$response1) {
                throw new \Exception("Registration failed.", 400);
            }
            $userAccountModel->user_source = "ws";
            $userAccountModel->user_id = $userloginModel->id;
            $userAccountModel->userAccountRegistration();
            ####################### Assign points user for registration #######################

            $points = $this->getAllocatedPoints('normalRegister');
            $points['type'] = "normalRegister";
            $message = "All life is a game. Here are 100 points to get you ahead of the game. Don't worry, it's not cheating.";
            $this->givePoints($points, $userloginModel->id, $message);


            ########## Notification to user on first Registration ########
            //$notificationMsg = 'Welcome to Munchado!';
            $notificationMsg = 'Welcome to Munch Ado! From now on, we’ll be helping you get from hangry to satisfied.';
            $channel = "mymunchado_" . $userloginModel->id;
            $notificationArray = array(
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userloginModel->id,
                "type" => 'registration',
                "restaurantId" => '0',
                'curDate' => $currentDate
            );
            $userNotificationModel->createPubNubNotification($notificationArray);
            StaticFunctions::pubnubPushNotification($notificationArray);

            if (!$loyalityCode || strtoupper($loyalityCode) === MUNCHADO_DINE_MORE_CODE) {
                $template = 'user-registration';
                $layout = 'email-layout/default_register';
                $variables = array(
                    'username' => $userloginModel->first_name,
                    'hostname' => $webUrl
                );
                $mailData = array('recievers' => $userloginModel->email, 'layout' => $layout, 'template' => $template, 'variables' => $variables);
                $this->sendRegistrationEmail($mailData);
            }


            $this->createSettings($userloginModel->id, $userloginModel->newsletter_subscribtion);

            $response1 = array_intersect_key($response1, array_flip(array(
                'id',
                'first_name',
                'last_name',
                'email'
            )));


            $session = StaticFunctions::getUserSession();
            $session->setUserId($userloginModel->id);
            $session->save();
            $userId = $userloginModel->id;
            $userEmail = $userloginModel->email;

            ############## Loyality Program Registration #############           
            if ($loyalityCode) {
                $this->registerRestaurantServer();
                $this->smsRegistrationPassword = $password = trim($this->generate_verification_code());
                $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
                $this->first_name = $userloginModel->first_name;
                $this->email = $userloginModel->email;
                $this->userId = $userId;
                $this->dineAndMoreAwards("awardsregistration");
                $this->loyaltyCode = $loyalityCode;
                $this->mailSmsRegistrationPassword($template);
            }
            ##########################################################
            ############## salesmanago Event ##################
            $salesData = [];
            $salesData['name'] = $userloginModel->first_name;
            $salesData['email'] = $userloginModel->email;
            $salesData['dine_more'] = ($this->loyaltyCode) ? "Yes" : "No";
            $salesData['owner_email'] = 'no-reply@munchado.com';
            $salesData['restaurant_name'] = ($this->loyaltyCode) ? $this->restaurant_name : "";
            $salesData ['restaurant_id'] = ($this->loyaltyCode) ? $this->restaurantId : "";
            $salesData['tags'] = ($this->loyaltyCode) ? array("Registration_form", "Dine_and_More") : array("Registration_form");
            $salesData['contact_ext_event_type'] = "OTHER";
            $salesData['redeempoint'] = 0;
            if ($loyalityCode) {
                $salesData['point'] = "200";
                $salesData['totalpoint'] = (int) $this->userTotalPoint($userId);
            } else {
                $salesData['point'] = "100";
                $salesData['totalpoint'] = (int) $this->userTotalPoint($userId);
            }
            $salesData['identifier'] = "register";
            //$this->createQueue($salesData, 'Salesmanago');
            ###################################################
            $cleverTap = [];
            if ($this->loyaltyCode) {
                $restDetails = $this->getRestOrderFeatures($this->restaurantId);
                //$isRestDineAndMore = $this->restaurantTaged($data ['restid']);
                $cleverTap['restaurant_dine_more'] = "yes";
                $cleverTap['restaurant_name'] = $this->restaurant_name;
                $cleverTap['restaurant_id'] = $this->restaurantId;
                $cleverTap['delivery_enabled'] = $restDetails['delivery'];
                $cleverTap['takeout_enabled'] = $restDetails['takeout'];
                $cleverTap['reservation_enabled'] = $restDetails['reservations'];
                $cleverTap['user_dine_more'] = "yes";
                $cleverTap['earned_points'] = 200;
            } else {
                $cleverTap['restaurant_dine_more'] = "no";
                $cleverTap['restaurant_name'] = "";
                $cleverTap['restaurant_id'] = "";
                $cleverTap['delivery_enabled'] = "";
                $cleverTap['takeout_enabled'] = "";
                $cleverTap['reservation_enabled'] = "";
                $cleverTap['user_dine_more'] = "no";
                $cleverTap['earned_points'] = 100;
            }

            $cleverTap['event'] = 1;
            $cleverTap['user_id'] = $userloginModel->id;
            $cleverTap['email'] = $userloginModel->email;
            $cleverTap['name'] = ($userloginModel->last_name) ? $userloginModel->first_name . " " . $userloginModel->last_name : $userloginModel->first_name;
            $cleverTap['identity'] = $userloginModel->email;
            $cleverTap['registration_date'] = $currentDate;
            $cleverTap['is_register'] = "yes";
            $cleverTap['eventname'] = "dine_and_more";

            $this->createQueue($cleverTap, 'clevertap');

            return $response1;
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong to register new user:' . $e->getMessage());
            throw new \Exception($e->getMessage(), 400);
        }
    }

    public function dmUserRegisterDuringOrder($userData) { //1385
        $userloginModel = new User();
        $userAccountModel = new UserAccount();
        $options = array(
            'where' => array(
                'email' => $this->email
            )
        );
        $userDetail = $userloginModel->getUserDetail($options);

        if (!empty($userDetail)) {
            return false;
        }

        $password = trim($this->generate_verification_code());
        $this->smsRegistrationPassword = $password;
        $this->loyaltyCode = $userData['loyality_code'];


        $userloginModel->first_name = $userData ['first_name'];
        $userloginModel->last_name = $userData['last_name'];
        $userloginModel->email = $userData ['email'];
        $userloginModel->password = md5($password);
        $userloginModel->phone = $userData['phone'];
        $userloginModel->city_id = $userData['cityid'];
        $userloginModel->newsletter_subscribtion = 1;
        $userloginModel->created_at = $userData['current_date'];
        $userloginModel->update_at = $userData['current_date'];
        $userloginModel->last_login = $userData['current_date'];
        $userloginModel->order_msg_status = '';
        $userloginModel->status = 1;
        $userloginModel->accept_toc = 1;
        $userloginModel->bp_status = 0;
        $userloginModel->display_pic_url = "noimage.jpg";
        $userloginModel->display_pic_url_normal = "noimage.jpg";

        $userloginModel->userRegistration();

        $userAccountModel->display_pic_url = "noimage.jpg";
        $userAccountModel->first_name = $userData['first_name'];
        $userAccountModel->user_source = $userData['user_source'];
        $userAccountModel->user_id = $userloginModel->id;
        $userAccountModel->userAccountRegistration();

        $points = $this->getAllocatedPoints('normalRegister');
        $points['type'] = "normalRegister";
        $message = "All life is a game. Here are 100 points to get you ahead of the game. Don't worry, it's not cheating.";
        $this->givePoints($points, $userloginModel->id, $message);

        $feed_name = $userloginModel->first_name . ' ' . $userloginModel->last_name;
        $feed = array(
            'user_id' => $userloginModel->id,
            'user_email' => $userDetail ['email'],
            'user_name' => ucfirst($feed_name)
        );
        $replacementData = array('message' => 'test');
        $otherReplacementData = array('user_name' => ucfirst($feed_name));
        $commonFunction = new \MCommons\CommonFunctions();
        $commonFunction->addActivityFeed($feed, 53, $replacementData, $otherReplacementData);

        #### User setting ####
        $this->createSettings($userloginModel->id, $userloginModel->newsletter_subscribtion);
        ######### Intigration of user reffer invitation ############

        $feed = array(
            'user_id' => $userloginModel->id,
            'user_name' => ucfirst($userloginModel->first_name),
            'restaurant_name' => ucfirst($userData['restaurant_name']),
            'restaurant_id' => $userData['restaurant_id']
        );
        $replacementData = array('restaurant_name' => ucfirst($userData['restaurant_name']));
        $otherReplacementData = array('restaurant_name' => ucfirst($userData['restaurant_name']), 'user_name' => ucfirst($userloginModel->first_name));
        $commonFunction->addActivityFeed($feed, 68, $replacementData, $otherReplacementData);


        ############## Loyality Program Registration #############           
        $this->userId = $userloginModel->id;
        $template = "Welcome_To_Restaurant_Dine_More_Rewards_New_User_Password"; //500N
        $this->first_name = $userloginModel->first_name;
        $this->email = $userloginModel->email;
        $this->userId = $userloginModel->id;
        $this->restaurant_name = $userData['restaurant_name'];
        $this->restaurantId = $userData['restaurant_id'];
        $this->registerRestaurantServer();
        $this->dineAndMoreAwardsDuringOrder("awardsregistration");
        $this->mailSmsRegistrationPassword($template);
        $userloginModel->updateUserPoint($userloginModel->id, 200);
        ############## salesmanago Event ##################
        $salesData = [];
        $salesData['name'] = $userloginModel->first_name;
        $salesData['email'] = $userloginModel->email;
        $salesData['dine_more'] = "Yes";
        $salesData['owner_email'] = 'no-reply@munchado.com';
        $salesData['restaurant_name'] = $userData['restaurant_name'];
        $salesData ['restaurant_id'] = $userData['restaurant_id'];
        $salesData['tags'] = array("Order_form", "Dine_and_More", $userData['restaurant_name']);
        $salesData['contact_ext_event_type'] = "OTHER";
        $salesData['redeempoint'] = 0;
        $salesData['point'] = 200;
        $salesData['totalpoint'] = 200;
        $salesData['identifier'] = "register";
        //$this->createQueue($salesData, 'Salesmanago');


        $clevertapData = array(
            "user_id" => $userloginModel->id,
            "name" => $feed_name,
            "email" => $userloginModel->email,
            "currentDate" => $userData['current_date'],
            "source" => $userData['user_source'],
            "loyalitycode" => $userData['loyality_code'],
            "restname" => $userData['restaurant_name'],
            "restid" => $userData['restaurant_id'],
            "eventname" => "dine_and_more",
        );

        $this->clevertapRegistrationEvent($clevertapData);

        ###################################################            
        return true;
    }

    public function dineAndMoreAwardsDuringOrder($awardType) {
        $type = "dine_more";
        $this->typeKey = 'registration_id';
        $this->typeValue = $this->userId;
        $points = $this->getAllocatedPoints($awardType);
        $commonFunctions = new CommonFunctions();
        $restaurantName = $commonFunctions->modifyRestaurantName($this->restaurant_name);
        $nMessage = "";
        if (!$this->isRegisterUser && ucfirst($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
            $message = "You joined " . $restaurantName . " Dine & More rewards program!";
            $nMessage = "Thank you for joining " . $restaurantName . " Dine & More rewards program, presented by Munch Ado!";
            $this->dineAndMoreNotification($nMessage, $type);
        } else {
            $message = "You joined " . $restaurantName . " Dine & More rewards program!";
        }

        if (ucfirst($this->loyaltyCode) != MUNCHADO_DINE_MORE_CODE) {
            $this->givePoints($points, $this->userId, $message);
        }
        return true;
    }

    public function sendCareerEmail($data) {
        $recievers = array(
            $data ['recievers']
        );
        $template = $data ['template'];
        $layout = $data ['layout'];
        $variables = $data ['variables'];
        $subject = $data['subject'];

        $emailData = array(
            'receiver' => $recievers,
            'variables' => $variables,
            'subject' => $subject,
            'template' => $template,
            'layout' => $layout,
            'sender' => $data['sender'],
            'sender_name' => $data['sender_name']
        );

        // #################
        return $this->sendMunchadoCareerMails($emailData);
    }

    public function sendMunchadoCareerMails($data, $sender = array()) {
        $recievers = array(
            $data ['receiver']
        );
        $template = "email-template/" . $data ['template'];
        $layout = (isset($data ['layout'])) ? $data ['layout'] : 'email-layout/default';
        return StaticFunctions::sendCareerMail($data['sender'], $data['sender_name'], $recievers, $template, $layout, $data ['variables'], $data ['subject']);
    }

    public function clevertapUploadProfile($data) {
        $cleverTap = [];
        $cleverTap['profile'] = 1;
        $cleverTap['name'] = $data['name'];
        $cleverTap['user_id'] = $data["user_id"];
        $cleverTap['identity'] = $data['email'];
        $cleverTap['email'] = $data['email'];
        $cleverTap['registration_date'] = $data['currentDate'];
        $cleverTap['host_url'] = WEB_URL;
        $cleverTap['is_register'] = "yes";

        if ($data['loyalitycode'] || $data['source'] === "sms" || $data['source'] === 'enrl') {
            $cleverTap['earned_points'] = 200;
            $cleverTap['redeemed_points'] = 0;
            $cleverTap['earned _dollar'] = StaticFunctions::convertPointToDollar(200);
            $cleverTap['remaining_points'] = 200;
            $cleverTap['remaining_dollar'] = $cleverTap['earned _dollar'];
            $cleverTap['user_dine_more'] = "yes";
            $cleverTap['restaurant_dine_more'] = "yes";
            $cleverTap['restaurant_name'] = $data['restname'];
            $cleverTap ['restaurant_id'] = $data['restid'];
            //$isRestDineAndMore = $this->restaurantTaged($data ['restid']);
            $cleverTap['restaurant_dine_more'] = "yes";
        } else {
            $cleverTap['earned_points'] = 100;
            $cleverTap['redeemed_points'] = 0;
            $cleverTap['earned _dollar'] = StaticFunctions::convertPointToDollar(100);
            $cleverTap['remaining_points'] = 100;
            $cleverTap['remaining_dollar'] = $cleverTap['earned _dollar'];
            $cleverTap['user_dine_more'] = "no";
            $cleverTap['restaurant_name'] = "no";
            $cleverTap ['restaurant_id'] = "no";
            $cleverTap['restaurant_dine_more'] = "no";
        }
        $cleverTap['registration_source'] = $data['source'];

        $this->createQueue($cleverTap, 'clevertap');
    }

    public function clevertapRegistrationEvent($data) {
        $cleverTap = [];

        if ($data['loyalitycode'] && strtoupper($data['loyalitycode']) != MUNCHADO_DINE_MORE_CODE) {
            $restDetails = $this->getRestOrderFeatures($data['restid']);
            //$isRestDineAndMore = $this->restaurantTaged($data ['restid']);
            $erp = (isset($data['refferralPoint']) && !empty($data['refferralPoint'])) ? 200 + $data['refferralPoint'] : "200";
            $cleverTap['restaurant_dine_more'] = "yes";
            $cleverTap['restaurant_name'] = $data['restname'];
            $cleverTap['restaurant_id'] = $data['restid'];
            $cleverTap['delivery_enabled'] = $restDetails[0]['delivery'];
            $cleverTap['takeout_enabled'] = $restDetails[0]['takeout'];
            $cleverTap['reservation_enabled'] = $restDetails[0]['reservations'];
            $cleverTap['user_dine_more'] = "yes";
            $cleverTap['earned_points'] = (string) $erp;
            $cleverTap['eventname'] = "dine_and_more";
        } else {
            $cleverTap['restaurant_dine_more'] = "no";
            $cleverTap['restaurant_name'] = "";
            $cleverTap['restaurant_id'] = "";
            $cleverTap['delivery_enabled'] = "";
            $cleverTap['takeout_enabled'] = "";
            $cleverTap['reservation_enabled'] = "";
            $cleverTap['user_dine_more'] = "no";
            $cleverTap['earned_points'] = "100";
            $cleverTap['eventname'] = "general";
        }
        if ($data['eventname'] === "refer_friend") {
            $cleverTap['reffered_email'] = $data['reffered_email'];
            $cleverTap['refer_date'] = $data['refer_date'];
        }
        $cleverTap['event'] = 1;
        $cleverTap['user_id'] = $data['user_id'];
        $cleverTap['email'] = $data['email'];
        $cleverTap['name'] = $data['name'];
        $cleverTap['identity'] = $data['email'];
        $cleverTap['registration_date'] = $data['currentDate'];
        $cleverTap['is_register'] = "yes";
        $cleverTap['host_url'] = isset($data['host_name']) ? $data['host_name'] : "";

        $this->createQueue($cleverTap, 'clevertap');
    }

    public function getRestOrderFeatures($restId) {
        $restaurantDetailModel = StaticFunctions::getServiceLocator()->get(Restaurant::class);
        return $restaurantDetailModel->findRestaurant(array('columns' => array('delivery', 'takeout', 'reservations'), 'where' => array('id' => $restId)));
    }

    public function restaurantStory($restaurantId) {
        $storyModel = new \Restaurant\Model\Story();
        $options = array('columns' => array('id', 'atmosphere', 'neighborhood', 'restaurant_history', 'chef_story', 'cuisine'), 'where' => array("restaurant_id" => $restaurantId), 'limit' => 1);
        $story = $storyModel->findStory($options)->toArray();
        if (!empty($story[0]['restaurant_history'])) {
            $restaurantStory = $story[0]['restaurant_history'];
        } elseif (!empty($story[0]['cuisine'])) {
            $restaurantStory = $story[0]['cuisine'];
        } elseif (!empty($story[0]['neighborhood'])) {
            $restaurantStory = $story[0]['neighborhood'];
        } elseif (!empty($story[0]['chef_story'])) {
            $restaurantStory = $story[0]['chef_story'];
        } elseif (!empty($story[0]['atmosphere'])) {
            $restaurantStory = $story[0]['atmosphere'];
        } else {
            $restaurantStory = "";
        }
        return $restaurantStory;
    }

    public function getPromocode($promocode, $restaurantId, $orderamount = false) {
        if ($promocode && $restaurantId) {
            $promocodeObj = new \Restaurant\Model\Promocodes();
            $options = array('promocode' => $promocode, 'restaurant_id' => $restaurantId);
            $userSession = StaticFunctions::getUserSession();
            $locationData = $userSession->getUserDetail('selected_location');
            $currentDate = $this->userCityTimeZone($locationData);
            $promocodeDetails = $promocodeObj->getPromocodeDetails($options);

            if ($promocodeDetails[0]['discount_type'] == 'flat') {
                $discountAmt = $promocodeDetails['0']['discount'];
            } else {
                $discountAmt = ($orderamount * $promocodeDetails['0']['discount']) / 100;
            }

            if ($orderamount && ($promocodeDetails[0]['promocodeType'] == 3 && $promocodeDetails[0]['budget'] < $discountAmt)) {
                return array("success" => false, "message" => "Invalid Promo code");
            }

            if ($promocodeDetails) {
                if ($promocodeDetails[0]['status'] != 1) {
                    return array("success" => false, "message" => "Promo code is expired");
                } else if (strtotime($promocodeDetails[0]['start_on']) <= strtotime($currentDate) && strtotime($currentDate) <= strtotime($promocodeDetails[0]['end_date'])) {
                    $promocodeDetails['0']['success'] = true;
                    return $promocodeDetails[0];
                } else {
                    return array("success" => false, "message" => "Promo code is expired");
                }
            }
        }
        return array("success" => false, "message" => "Invalid Promo code");
    }

    public function getMaPromocodelist($restaurantId) {
        if ($restaurantId) {
            $promocodeObj = new \Restaurant\Model\Promocodes();
            $options = array('restaurant_id' => $restaurantId, 'status' => 1);
            $currentDate = StaticFunctions::getRelativeCityDateTime(array(
                        'restaurant_id' => $restaurantId
                    ))->format('Y-m-d h:i');

            $promocodeDetails = $promocodeObj->getMaRestaurantPromocode($options);

            $promocodeList = [];
            if ($promocodeDetails) {
                foreach ($promocodeDetails as $key => $val) {
                    if (strtotime($promocodeDetails[$key]['start_on']) <= strtotime($currentDate) && strtotime($currentDate) <= strtotime($promocodeDetails[$key]['end_date'])) {
                        $promocodeList[] = $promocodeDetails[$key];
                    }
                }
            } else {
                return $promocode = array("have_promo" => false, "message" => "Promocode not available", "promocode" => array());
            }
        }

        if (empty($promocodeList)) {
            $promocode = array("have_promo" => false, "message" => "Promocode not available", "promocode" => array());
        } else {
            $promocode = array("have_promo" => true, "message" => "success", "promocode" => $promocodeList);
        }
        return $promocode;
    }

}
