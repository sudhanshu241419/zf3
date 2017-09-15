<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;

class UserSetting extends AbstractModel {

    public $id;
    public $user_id;
    public $order_confirmation;
    public $order_delivered;
    public $reservation_confirmation;
    public $deal_coupon_purchased;
    public $monthly_points_summary;
    public $comments_on_reviews;
    public $system_updates;
    public $friend_acceptance_on_group_orders;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        $this->_tableGateway = $tableGateway;
        parent::__construct($tableGateway);
    }

    public function findUserSettings($user_id = 0) {
        $data = array();
        $getUserSettings = $this->find(array(
                    'where' => array(
                        'user_id' => $user_id
                    )
                ))->current();
        if ($getUserSettings) {
            $data ['notification_setting'] ['id'] = $getUserSettings->id;
            $data ['notification_setting'] ['user_id'] = $getUserSettings->user_id;
            $data ['notification_setting'] ['order_confirmation'] = $getUserSettings->order_confirmation;
            $data ['notification_setting'] ['order_delivered'] = $getUserSettings->order_delivered;
            $data ['notification_setting'] ['reservation_confirmation'] = $getUserSettings->reservation_confirmation;
            $data ['notification_setting'] ['deal_coupon_purchased'] = $getUserSettings->deal_coupon_purchased;
            $data ['notification_setting'] ['monthly_points_summary'] = $getUserSettings->monthly_points_summary;
            $data ['notification_setting'] ['comments_on_reviews'] = $getUserSettings->comments_on_reviews;
            $data ['notification_setting'] ['system_updates'] = $getUserSettings->system_updates;
            $data ['notification_setting'] ['friend_acceptance_on_group_orders'] = $getUserSettings->friend_acceptance_on_group_orders;
        }
        return $data;
    }

    public function getUserSettingsDetail($user_id = 0) {
        $select = new Select ();
        $select->from($this->getDbTable()->getTableName());
        $select->columns(array(
            'id',
            'user_id',
            'order_confirmation',
            'order_delivered',
            'reservation_confirmation',
            'deal_coupon_purchased',
            'monthly_points_summary',
            'comments_on_reviews',
            'system_updates',
            'friend_acceptance_on_group_orders'
        ));
        $select->join(array(
            'u' => 'users'
                ), 'user_settings.user_id=u.id', array(
            'email'
                ), $select::JOIN_LEFT);
        $select->where(array(
            'user_settings.user_id' => $user_id
        ));
        $userAddressDetails = $this->getDbTable()->setArrayObjectPrototype('ArrayObject')->getReadGateway()->selectWith($select);
        return $userAddressDetails->toArray();
    }

    public function update($data) {
        $this->getDbTable()->getWriteGateway()->update($data, array(
            'user_id' => $this->user_id
        ));
        return true;
    }

    public function getUserSettingStatus($userId, $flag = NULL) {
        $userNotification = $this->findUserSettings($userId);
        $sendMailToUser = false;
        $userSettingStatus = 0;
        if ($userNotification) {
            if ($flag == 'reservation') {
                $userSettingStatus = $userNotification ['notification_setting'] ['reservation_confirmation'];
            } elseif ($flag == 'orderconfirm') {
                $userSettingStatus = $userNotification ['notification_setting'] ['order_confirmation'];
            } elseif ($flag == 'review') {
                $userSettingStatus = $userNotification ['notification_setting'] ['comments_on_reviews'];
            }
            $status = $userSettingStatus;
            $sendMailToUser = ($status == 0 || $status == NULL) ? false : true;
            return $sendMailToUser;
        } else {
            return true;
        }
    }

    public function create1($userId, $ns = false) {
        $data = array(
            'user_id' => $userId,
            'order_confirmation' => 1,
            'order_delivered' => 1,
            'reservation_confirmation' => 1,
            'comments_on_reviews' => 1,
            'new_order' => 1,
            'new_reservation' => 1,
            'comments_on_reviews' => 1,
            'friend_request' => 1,
            'system_updates' => ($ns == 1) ? 1 : 0,
        );
        
        $this->_tableGateway->insert($data);
        return true;
    }

    public function notificationSetting($data = array()) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        if (!empty($data)) {
            $settingData = array(
                "user_id" => $data['user_id'],
                "order_confirmation" => $data['order_confirmation'],
                "order_delivered" => $data['order_delivered'],
                "reservation_confirmation" => $data['reservation_confirmation'],
                "deal_coupon_purchased" => $data['deal_coupon_purchased'],
                "monthly_points_summary" => $data['monthly_points_summary'],
                "comments_on_reviews" => $data['comments_on_reviews'],
                "system_updates" => $data['system_updates'],
                "friend_acceptance_on_group_orders" => $data['friend_acceptance_on_group_orders']
            );
            $rowsAffected = $writeGateway->insert($settingData);
            if ($rowsAffected) {
                $lastInsertId = $writeGateway->getAdapter()->getDriver()->getLastGeneratedValue();
                if ($lastInsertId)
                    return true;
                else
                    return false;
            }else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function insert($data) {
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($data);
        $lastInsertId = $writeGateway->getAdapter()->getDriver()->getLastGeneratedValue();
        return $lastInsertId;
    }

}
