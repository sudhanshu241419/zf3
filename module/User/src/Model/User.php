<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use MCommons\StaticFunctions;
use Authenticationchanel\Model\Authenticationchanel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use User\Model\UserFriends;
use User\Model\UserInvitation;
use Restaurant\Model\RestaurantBookmark;
use Restaurant\Model\MenuBookmark;
use User\Model\UserPoint;
use User\Model\CheckinImages;
use User\Model\UserReviewImage;
use User\Model\UserReview;
use User\UserFunctions;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Insert;

class User extends AbstractModel {

    public $id;
    public $user_name;
    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $mobile;
    public $phone;
    public $display_pic_url;
    public $user_source;
    public $accept_toc;
    public $newsletter_subscribtion;
    public $created_at;
    public $update_at;
    public $points;
    public $billing_address;
    public $shipping_address;
    public $status;
    public $display_pic_url_normal;
    public $display_pic_url_large;
    public $delivery_instructions;
    public $takeout_instructions;
    public $order_msg_status = 0;
    public $session_token;
    public $last_login;
    public $access_token;   
    public $city_id;
    public $bp_status;
    public $registration_subscription = 0;
    public $wallpaper = NULL;
    public $referral_code = NULL;
    public $referral_ext = NULL;
    public $wallet_balance = 0;
    protected $_tableGateway;
    public function __construct(TableGatewayInterface $tableGateway) {
        $this->_tableGateway = $tableGateway;  
        parent::__construct($tableGateway);        
    }  
    public function getUserDetail(array $options = array()) { 
        $response = $this->find($options)->current();        
        return $response;
    }

    public function getUserByEmail($userEmail) {
        $joins_user = array();
        $joins_user [] = array(
            'name' => array(
                'ua' => 'user_account'
            ),
            'on' => 'users.id = ua.user_id',
            'columns' => array(
                'first_name',
                'last_name',
            ),
            'type' => 'inner'
        );
        $options = array(
            'columns' => array(
                'id',
                'email'
            ),
            'where' => array('users.email' => $userEmail),
            'joins' => $joins_user,
        );
        $userDetail = $this->find($options)->current();
        return $userDetail;
    }

    public function getUserByEmailMob($userEmail) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $joins_user = array();
        $options = array(
            'columns' => array(
                'id',
                'email',
                'first_name',
                'last_name'
            ),
            'where' => array('users.email' => $userEmail)
        );
        $userDetail = $this->find($options)->current();
        return $userDetail;
    }

    public function getUser($options) {
        $userDetail = $this->find($options)->toArray();
        return current($userDetail);
    }

    public function userRegistration() {
        $data = $this->toArray();
        
        if (!$this->id) {
            $rowsAffected = $this->_tableGateway->insert($data);
           // Get the last insert id and update the model accordingly
            $lastInsertId = $this->_tableGateway->lastInsertValue;
        } else {
            $rowsAffected = $this->_tableGateway->update($data, array(
                'id' => $this->id
            ));
            $lastInsertId = $this->id;
        }

        if ($rowsAffected >= 1) {
            $this->id = $lastInsertId;            
            return $this->toArray();
        }
        return false;
    }

    public function create_user_auth($user_data) {
        $authenticationchanel = new Authenticationchanel();
        $user_data['channel'] = Authenticationchanel::FORM;
        $last_name = (isset($user_data['last_name'])) ? $user_data['last_name'] : '';
        $user_data['name'] = $user_data['first_name'] . ' ' . $last_name;
        $auth_data = $this->prepare_input_data_to_create_authentication_channel($user_data);
        $auth_data['user_id'] = $this->id;
        $auth_data['created_at'] = StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT);
        $auth_data['updated_at'] = StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT);
        try {
            $auth = $authenticationchanel->create($auth_data);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function prepare_input_data_to_create_authentication_channel($user_data) {
        $input_data['name'] = $this->get_value($user_data, 'first_name') . " " . User::get_value($user_data, 'last_name');
        $input_data['email'] = $this->get_value($user_data, 'email');
        $input_data['channel'] = $this->get_value($user_data, 'channel');
        $input_data['access_token'] = $this->get_value($user_data, 'access_token');
        $input_data['uid'] = $this->get_value($user_data, 'identifier');
        $input_data['display_pic_url'] = $this->get_value($user_data, 'photoURL');
        $input_data['response'] = json_encode($user_data);
        return $input_data;
    }

    public function is_present($value) {
        return (($value != null && $value != '') ? true : false);
    }

    public function get_value($input_array, $key) {
        if (isset($input_array[$key]) && $this->is_present($input_array[$key]))
            return $input_array[$key];

        return null;
    }

    public function update_order_payee_id($user_id, $email) {
        $objPreorder = new PreOrder();
        $pre_order_data = $objPreorder->getAllPreorder($email);
        if (!empty($pre_order_data)) {
            $objPreorder->updateOrderPayee($pre_order_data, $user_id);
        }

        $objUserOrderInvitation = new UserOrderInvitation();
        $invited_data = $objUserOrderInvitation->getInvitedData($email);
        if (!empty($invited_data)) {
            $objUserOrderInvitation->updateInvitationData($invited_data, $user_id);
        }
    }

    public function update_pre_order_for_payee() {
        $session = $this->getUserSession();
        $UserOrderInvitation = new UserOrderInvitation();

        if ($session->getUserDetail('invitee_email')) {
            $email = $session->getUserDetail('invitee_email');
        }
        if ($session->getUserDetail('invitee_token_coded')) {
            $token_coded = $session->getUserDetail('invitee_token_coded');
        }
        if ($invitee_acceptance) {
            $acceptance = $_SESSION['invitee_acceptance'];
        }
        if ($session->getUserDetail('invitee_return')) {
            $return = $session->getUserDetail('invitee_return');
        }
        if ($session->getUserDetail('invitee_payee')) {
            $payee = $session->getUserDetail('invitee_payee');
        }

        $current_user_id = $session->getUserDetail('user_id');

        if (!empty($email) && !empty($token_coded))
            $UserOrderInvitation->verifying_invitation_process($email, $token_coded, $acceptance, $return, $payee, $current_user_id);

        $session_keys = array(
            'invitee_email',
            'invitee_token_coded',
            'invitee_acceptance',
            'invitee_return',
            'invitee_payee'
        );

        foreach ($session_keys as $key => $value) {
            unset($_SESSION[$value]);
        }
    }

    public function update($data) {
          $rowsAffected = $this->_tableGateway->update($data, array(
            'id' => $this->id
            ));
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    public function countUserPoints($user_id) {
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array(
            'points'
        ));
        $where = new Where();
        $where->equalTo('id', $user_id);
        $where->equalTo('status', 1);
        $select->where($where);
        $userPoints = $this->_tableGateway->selectWith($select);
        return $userPoints->toArray();
    }

    public function formatResultSet($response) {
        $cards = array();
        $address = array();
        $filtered = array();
        $user = array();
        $notification = array();
        if (!empty($response)) {
            foreach ($response as $single) {
                if (!isset($cards[$single['card_id']])) {
                    $cards[$single['card_id']]['card_id'] = $single['card_id'];
                    $cards[$single['card_id']]['card_number'] = $single['card_number'];
                    $cards[$single['card_id']]['name_on_card'] = $single['name_on_card'];
                    $cards[$single['card_id']]['card_type'] = $single['card_type'];
                    $cards[$single['card_id']]['expired_on'] = $single['expired_on'];
                }
                if (!isset($address[$single['address_id']])) {
                    $address[$single['address_id']]['address_id'] = $single['address_id'];
                    $address[$single['address_id']]['city'] = $single['city'];
                    $address[$single['address_id']]['state'] = $single['state'];
                    $address[$single['address_id']]['phone'] = $single['phone'];
                    $address[$single['address_id']]['apt_suite'] = $single['apt_suite'];
                    $address[$single['address_id']]['street'] = $single['street'];
                }
            }
            $cards = array_values($cards);
            $address = array_values($address);
            $user['display_pic_url'] = $response[0]['display_pic_url'];
            $user['first_name'] = $response[0]['first_name'];
            $user['last_name'] = $response[0]['last_name'];
            $user['phone'] = $response[0]['phone'];
            $user['email'] = $response[0]['email'];
            $notification['order_confirmation'] = $response[0]['order_confirmation'];
            $notification['order_delivered'] = $response[0]['order_delivered'];
            $notification['reservation_confirmation'] = $response[0]['reservation_confirmation'];
            $notification['deal_coupon_purchased'] = $response[0]['deal_coupon_purchased'];
            $notification['monthly_points_summary'] = $response[0]['monthly_points_summary'];
            $notification['system_updates'] = $response[0]['system_updates'];
            $notification['comments_on_reviews'] = $response[0]['comments_on_reviews'];
            $notification['friend_acceptance_on_group_orders'] = $response[0]['friend_acceptance_on_group_orders'];
            $user['email_notifications_registered'] = $notification;
            $user['my_delivery_details'] = $address;
            $user['my_payment_details'] = $cards;
            return $user;
        }
        throw new \Exception('No User Details Found');
    }

    public function getUserDetailWithStatistics($userId, $currentUserId = null) {
        $UserDetail = array();
        $userFriendModel = new UserFriends();
        $userOrderModel = new UserOrder();
        $reservationModel = new UserReservation();
        $resBookmarkModel = new RestaurantBookmark();
        $foodBookmarkModel = new MenuBookmark();
        $userPoint = new UserPoint();
        $reviewModel = new UserReview();
        $tipModel = new \User\Model\UserTip();
        $userReservationInviteModel = new UserInvitation();
        $checkinModel = new \User\Model\UserCheckin();
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'id',
            'first_name',
            'last_name',
            'email',
            'billing_address',
            'shipping_address',
            'display_pic_url',
            'display_pic_url_normal',
            'display_pic_url_large',
            'created_at',
            'last_login',
            'city_id',
            'wallpaper'
        ));
        /* $select->join(array(
          'ui' => 'user_statistics'
          ), 'ui.user_id = users.id', array(
          'my_points',
          'order_count',
          'reservation_count',
          'deals_count',
          'friends_count',
          'bookmarks_count',
          'reviews_count',
          'groups_order_count',
          ), $select::JOIN_LEFT); */

        $select->where->equalTo('users.id', $userId);

        $UserDetail = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select)
                ->current();


        if ($UserDetail) {
            $UserDetail = $UserDetail->getArrayCopy();
            $UserDetail['created_at'] = StaticFunctions::getFormattedDateTime($UserDetail['created_at'], 'Y-m-d H:i:s', 'M d, Y');
            $totalPoints = $userPoint->countUserPoints($userId);
            $redeemPoint = $totalPoints[0]['redeemed_points'];
            $points = strval($totalPoints[0]['points'] - $redeemPoint);
            $orders = current($userOrderModel->getTotalUserOrders($userId, 'I'));
            $reservations = current($reservationModel->getTotalUserReservations($userId));
            $friends = $userFriendModel->getTotalUserFriends($userId);
            $checkin = $checkinModel->getTotalUsercheckin($userId);
            $resBookmark = $resBookmarkModel->getUserBookmarkForRestaurantCount($userId);
            $foodBookmark = $foodBookmarkModel->getUserMenuesBookmarkCount($userId);
            $reviews = $reviewModel->getUserTotalRreview($userId);
            $tips = $tipModel->getUserTotalTip($userId);
            $ordersGroup = current($userOrderModel->getTotalUserOrders($userId, 'G'));
            $totBookmark = $resBookmark + $foodBookmark;
            $UserDetail['my_points'] = isset($points) ? $points : 0;
            $UserDetail['order_count'] = $orders['total_order'];
            $UserDetail['reservation_count'] = $reservations['total_reservation'];
            $UserDetail['deals_count'] = 0;
            $UserDetail['friends_count'] = $friends['total_friend'];
            $UserDetail['bookmarks_count'] = $totBookmark;
            $UserDetail['reviews_count'] = $reviews['total_review'] + $tips['total_tip'];
            $UserDetail['groups_order_count'] = $ordersGroup['total_order'];
            $UserDetail['common_friends_count'] = $userFriendModel->getCommonFriends($userId, $currentUserId);
            $UserDetail['reservation_with_count'] = $userReservationInviteModel->getReservationInviCount($userId, $currentUserId);
            $UserDetail['total_checkin'] = $checkin[0]['total_checkin'];
            $UserDetail['wallpaper'] = $UserDetail['wallpaper'];

            return $UserDetail;
        }

        return $UserDetail;
    }
    
    public function getUserDetailWithStatisticsDetails($userId, $currentUserId = null) {
        $UserDetail = array();
        $userFriendModel = new UserFriends();
        $userOrderModel = new UserOrder();
        $reservationModel = new UserReservation();
        $resBookmarkModel = new RestaurantBookmark();
        $foodBookmarkModel = new MenuBookmark();
        $userPoint = new UserPoint();
        $reviewModel = new UserReview();
        $tipModel = new \User\Model\UserTip();
        $userReservationInviteModel = new UserInvitation();
        $checkinModel = new \User\Model\UserCheckin();
        $snagASport = new \Restaurantdinein\Model\Restaurantdinein();
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'id',
            'first_name',
            'last_name',
            'email',
            'billing_address',
            'shipping_address',
            'display_pic_url',
            'display_pic_url_normal',
            'display_pic_url_large',
            'created_at',
            'last_login',
            'city_id',
            'wallpaper'
        ));
        /* $select->join(array(
          'ui' => 'user_statistics'
          ), 'ui.user_id = users.id', array(
          'my_points',
          'order_count',
          'reservation_count',
          'deals_count',
          'friends_count',
          'bookmarks_count',
          'reviews_count',
          'groups_order_count',
          ), $select::JOIN_LEFT); */

        $select->where->equalTo('users.id', $userId);

        $UserDetail = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select)
                ->current();


        if ($UserDetail) {
            $UserDetail = $UserDetail->getArrayCopy();
            $UserDetail['created_at'] = StaticFunctions::getFormattedDateTime($UserDetail['created_at'], 'Y-m-d H:i:s', 'M d, Y');
            $totalPoints = $userPoint->countUserPoints($userId);
            $redeemPoint = $totalPoints[0]['redeemed_points'];
            $points = strval($totalPoints[0]['points'] - $redeemPoint);
            $orders = current($userOrderModel->getTotalUserOrders($userId, 'I'));
            $reservations = current($reservationModel->getTotalUserReservations($userId));
            $friends = $userFriendModel->getTotalUserFriends($userId);
            $checkin = $checkinModel->getTotalUsercheckin($userId);
            $resBookmark = $resBookmarkModel->getUserBookmarkForRestaurantCount($userId);
            $foodBookmark = $foodBookmarkModel->getUserMenuesBookmarkCount($userId);
            $reviews = $reviewModel->getUserTotalRreview($userId);
            $tips = $tipModel->getUserTotalTipForDetails($userId);
            $ordersGroup = current($userOrderModel->getTotalUserOrders($userId, 'G'));
            $totBookmark = $resBookmark + $foodBookmark;
            $UserDetail['my_points'] = isset($points) ? $points : 0;
            $UserDetail['order_count'] = $orders['total_order'];
            $UserDetail['reservation_count'] = $reservations['total_reservation'];
            $UserDetail['deals_count'] = 0;
            $UserDetail['friends_count'] = $friends['total_friend'];
            $UserDetail['bookmarks_count'] = $totBookmark;
            $UserDetail['reviews_count'] = $reviews['total_review'] + $tips['total_tip'];
            $UserDetail['groups_order_count'] = $ordersGroup['total_order'];
            $UserDetail['common_friends_count'] = $userFriendModel->getCommonFriends($userId, $currentUserId);
            $UserDetail['reservation_with_count'] = $userReservationInviteModel->getReservationInviCount($userId, $currentUserId);
            $UserDetail['total_checkin'] = $checkin[0]['total_checkin'];
            $UserDetail['wallpaper'] = $UserDetail['wallpaper'];
            $UserDetail['snag_a_spot_count'] = $snagASport->totalSnagaSport($userId);

            return $UserDetail;
        }

        return $UserDetail;
    }
    
    
    public function getName($userId) {
        $record = $this->getUserDetail(array(
            'columns' => array(
                'first_name',
                'last_name'
            ),
            'where' => array(
                'id' => $userId
            )
        ));

        if (!empty($record)) {
            $record->getArrayCopy();
            return $record['first_name'] . ' ' . $record['last_name'];
        }
        return "";
    }

    public function updateUserPoint($id, $point) {
        $data = array(
            'points' => $point
        );
        
        $dataUpdated = $this->_tableGateway->update($data, array(
            'id' => $id
        ));
        if ($dataUpdated == 0) {
            return array(
                'error' => 'User point not found'
            );
        } else {
            return array(
                'success' => 'true'
            );
        }
    }

    public function getDpImagesForUpload($user_id = null) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $options = array(
            'columns' => array(
                'id',
                'display_pic_url',
                'display_pic_url_normal',
                'display_pic_url_large'
            ),
            'where' => array(
                'image_status' => 1,
                'display_pic_url NOT LIKE "%http://%"',
                'display_pic_url NOT LIKE "%https://%"'
            )
        );
        if ($user_id != null) {
            $options['where']['id'] = $user_id;
        }
        $rows = $this->find($options)->toArray();
        return $rows;
    }

    public function updateImageStatus($id) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->update(array(
            'image_status' => 2
                ), array(
            'id' => $id
        ));
        return $rowsAffected;
    }

    public function checkUserForMail($userId = NULL, $flag = NULL) {
        if (empty($userId)) {
            return true;
        } elseif (!empty($userId)) {
            $record = $this->getUserDetail(array(
                'columns' => array(
                    'status',
                ),
                'where' => array(
                    'id' => $userId
                )
            ));
            if ($record['status'] == 1) {
                $userSetting = new UserSetting();
                $sendMail = $userSetting->getUserSettingStatus($userId, $flag);
                return $sendMail;
            }
        }
    }

    public function getUserEatingHabitsDetail($user_id = 0) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'first_name',
            'last_name',
            'city_id',
            'phone',
            'email',
        ));
        $select->join(array(
            'ueh' => 'user_eating_habits'
                ), 'ueh.user_id=users.id', array(
            'eating_habits_id' => 'id',
            'favorite_beverage',
            'where_do_you_go',
            'comfort_food',
            'favorite_food',
            'dinner_with',
                ), $select::JOIN_LEFT);
        $select->where(array(
            'users.id' => $user_id
        ));

        $userEatingHabitDetails = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $userEatingHabitDetails->toArray();
    }

    public function getUserEmailSubscriber($userEmail) {        
        $options = array(
            'columns' => array(
                'id',
                'user_name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'created_at',
                'registration_subscription'
            ),
            'where' => array(
                'email' => $userEmail
            )
        );

        if ($this->find($options)) {
            $userDetail = $this->find($options)->current();
        } else {
            $userDetail = [];
        }
        return $userDetail;
    }

    /*
     * This function is use to get total image count for a user
     *  parameter user :user id
     *  return total_images
     *  function date created 6/8/2015
     */

    public function userTotalImages($uId = null) {
        $checkinImagesModel = new CheckinImages();
        $userReviewImageModel = new UserReviewImage();
        $UserMenuReview = new UserReview();
        $checkinImages = $checkinImagesModel->checkinTotalImages($uId);
        $userReviewImage = $userReviewImageModel->userTotalReviewImage($uId);
        $UserMenuTotalReview = $UserMenuReview->UserMenuTotalReview($uId);
        $totalCheckImage = isset($checkinImages[0]['images']) ? $checkinImages[0]['images'] : 0;
        $totalReviewImage = isset($userReviewImage[0]['images']) ? $userReviewImage[0]['images'] : 0;
        $totalMenuImage = isset($UserMenuTotalReview) ? $UserMenuTotalReview : 0;
        $otal = (int) $totalCheckImage + (int) $totalReviewImage + (int) $totalMenuImage;
        return (int) $otal;
    }

    public function getFirstName($userId) {
        $record = $this->getUserDetail(array(
            'columns' => array(
                'first_name',
                'last_name'
            ),
            'where' => array(
                'id' => $userId
            )
        ));

        if (!empty($record)) {
            $record->getArrayCopy();
            return $record['first_name'];
        }
        return "";
    }

    /* This function is use to get all user ids
     *  parameter 0
     *  return variable $users
     */

    public function getAllUserIds() {
        $options = array('columns' => array('id'));
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $userDetail = $this->find($options)->toArray();
        return $userDetail;
    }

    public function getUserEmail($id = false) {
        $options = array('columns' => array('id', 'email','status'), 'where' => array('id' => $id));
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $userDetail = $this->find($options)->current();
        return $userDetail;
    }

    public function getReferralCodeDetails($referral_code) {
        $record = $this->getUserDetail(array(
            'columns' => array('id', 'first_name', 'last_name', 'email', 'display_pic_url'),
            'where' => array('referral_code' => $referral_code)
        ));
        if (!empty($record)) {
            $data = $record->getArrayCopy();
            $userFunctions = new UserFunctions();
            $user_pic = $userFunctions->findImageUrlNormal($data['display_pic_url'], $data['id']);
            if (preg_match('/no_img.jpg/', $user_pic)) {
                $user_pic = '';
            }
            $data['display_pic_url'] = $user_pic;
            $data['referral_code'] = $referral_code;
            return $data;
        }
        return FALSE;
    }

    /**
     * 
     * @param int $user_id
     * @param string $device {web, mob}
     * @return string referral_code/personalized for web and referral_code for mob
     * @throws \Exception
     */
    public function getUserReferralCode($userId, $device = 'false') {
        $options = array(
            'columns' => array('id', 'referral_code', 'referral_ext'),
            'where' => array('id' => $userId)
        );

        //if user_has valid referral_code, return it
        $userDetail = $this->find($options)->current();
        //vd($userDetail,1);
        if ($userDetail && (count($userDetail['referral_code']) > 0)) {
            $referral_code = $userDetail['referral_code'];
            if (count($userDetail['referral_ext']) > 0 && !$device) {
                $referral_code .= '/' . $userDetail['referral_ext'];
            }
            return $referral_code;
        }

        //no referral code found. creat it.
        //$writeGateway = $this->getDbTable()->getWriteGateway();
        //$userFunctions = 
        $referral_code = \User\Functions\UserFunctions::generateUserReferralCode($userId);
        $data = array('referral_code' => $referral_code, 'referral_ext' => null);
        $rowsAffected = $this->_tableGateway->update($data, array('id' => $userId));
        if (!$rowsAffected == 1) {
            throw new \Exception('Referral code could not be created.');
        }
        return $referral_code;
    }

    /**
     * Check if $refCode exists in users table.
     * @param String $refCode
     * @return boolean
     */
    public function hasRefCode($refCode) {
        $options = array(
            'columns' => array('id', 'referral_code'),
            'where' => array('referral_code' => $refCode)
        );
        //if user_has valid referral_code, return it
        $refDetail = $this->find($options)->current();
        if ($refDetail) {
            return true;
        }
        return false;
    }

    public function existsUserWithEmail($email) {
        $options = array('columns' => array('id', 'email'), 'where' => array('email' => $email));
        $userDetail = $this->find($options)->toArray();
        return $userDetail;
    }

    public function setUserReferralExt($referral_ext, $user_id) {
        $referral_ext = trim($referral_ext);
        if (count($referral_ext) == 0) {
            return false;
        }
        $data = array('referral_ext' => $referral_ext);
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $writeGateway->update($data, array('id' => $user_id));
        return true;
    }

    public function setUserWalleTBalance($user_id) {
        $session = StaticFunctions::getUserSession();
        $locationData = $session->getUserDetail('selected_location', array());
        $currentDateTime = UserFunctions::userCityTimeZone($locationData);
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->update(array(
            'wallet_balance' => 5,
            'update_at' => $currentDateTime
                ), array(
            'id' => $user_id,
        ));
        if ($rowsAffected) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update user's wallet after each transaction.
     * @param array $data required keys user_id, transaction_type, transaction_amount
     * @return boolean
     * @throws \Exception if wallet could not be updated
     */
    public function updateUserWallet($data) {
        if (!isset($data['user_id']) || !isset($data['transaction_type']) || !isset($data['transaction_amount'])) {
            throw new \Exception('Invalid method call');
        }
        $update = new \Zend\Db\Sql\Update();
        $update->table($this->getDbTable()->getTableName());
        $where = new Where();
        $where->equalTo('id', $data['user_id']);
        $update->where($where);
        if ($data['transaction_type'] == 'credit') {
            $expression = new \Zend\Db\Sql\Expression("`wallet_balance` + " . $data['transaction_amount']);
        } elseif ($data['transaction_type'] == 'debit') {
            $expression = new \Zend\Db\Sql\Expression("`wallet_balance` - " . $data['transaction_amount']);
        } else {
            throw new \Exception('Invalid transaction_type.');
        }

        $update->set(array('wallet_balance' => $expression, 'update_at' => date("Y-m-d H:i:s")));
        $rows_affected = $this->getDbTable()->getWriteGateway()->updateWith($update);
        if ($rows_affected != 1) {
            throw new \Exception('Could not update wallet balance.');
        }
        return true;
    }

    public function getAUser($options) {
        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');        
        $userDetail = $this->find($options)->toArray();
        return $userDetail;
    }

    public function getUserNotOrder() {

        $this->getDbTable()->setArrayObjectPrototype('ArrayObject');
        $joins_user = array();
        $joins_user [] = array(
            'name' => array(
                'uo' => 'user_orders'
            ),
            'on' => 'users.id = uo.user_id',
            'columns' => array(
                'order_id' => 'id'
            ),
            'type' => 'left'
        );
        $options = array(
            'columns' => array(
                'id',
                'first_name',
                'email',
                'city_id',
                'join_on' => 'created_at'
            ),
            'where' => 'users.city_id = 18848 and users.created_at < "2016-05-16"', //array('users.city_id' => 18848),
            'joins' => $joins_user,
            'order' => array('join_on'),
            'group' => 'users.id'
        );
        $userDetail = $this->find($options)->toArray();
        return $userDetail;
    }
    public function getTotalUsers() {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns(array(
            'total_users' => new Expression('COUNT(id)'),
        ));
        $select->join(array(
            'rs' => 'restaurant_servers'
                ), 'rs.user_id <> user_orders.user_id', array(
                ), $select::JOIN_INNER);
        $where = new Where ();
        $where->equalTo('status', 1);
        $select->where($where);
        //var_dump($select->getSqlString($this->getPlatform('READ')));die;
        $data = $this->_tableGateway->selectWith($select)->toArray();
        if (empty($data)) {
            return $data[0]['total_users'] = 0;
        } else {
            return $data[0]['total_users'];
        }
    }  

}
