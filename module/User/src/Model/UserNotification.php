<?php

namespace User\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use MCommons\StaticFunctions;
use Zend\Db\Sql\Predicate\Expression;
use User\Model\UserOrder;
use User\Functions\UserFunctions;

class UserNotification extends AbstractModel {

    public $id;
    public $user_id;
    public $msg;
    public $type;
    public $restaurant_id;
    public $channel;
    public $created_on;
    public $status;
    public $pubnub_info;
    protected $_tableGateway;
    
    public static $imageClass = [
        2 => "i_order",
        1 => "i_order",
        3 => "i_reserve_a_table active",
        4 => "i_ratereview",
        0 => "i_reserve_a_table",
        5 => "i_twopeople",
        6 => "i_ratereview",
        7 => "i_tip",
        8 => "i_upload_photo",
        9 => "i_bookmark",
        10 => "i_friendship",
        11 => "checkin",
        12 => "feed",
        13 => "transactions",
        14 => "i_point",
        15 => "i_reserve_a_table",
        16 => "i_deal",
        17 => "i_reserve_a_table",
        18 => "i_bill",
        19 => "i_reserve_a_table active"
    ];
    public static $orderType = [
        2 => "myorders",
        1 => "myorders",
        3 => "myreservations",
        4 => "reviews",
        0 => "mymunchado",
        5 => "myfriends",
        6 => "myreviews",
        7 => "tip",
        8 => "upload_photo",
        9 => "bookmark",
        10 => "friendship",
        11 => "checkin",
        12 => "feed",
        13 => "transactions",
        14 => "mypoints",
        15 => "i_reserve_a_table",
        16 => "deal",
        17 => "dine_more",
        18 => "bill",
        19 => "snagaspot",
    ];

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
    }

    public function getCurrentNotification($userId, $limit = NULL, $type, $todayDate) {
        $today = date('Y-m-d H:i:s', strtotime("-30 days", strtotime($todayDate)));
        $userOrderMoel = StaticFunctions::getServiceLocator()->get(UserOrder::class);
        $myCurrentNotification = [];
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(array('id', 'msg' => 'notification_msg', 'type', 'restaurant_id', 'created_on', 'status', 'pubnub_info'));
        $where = new Where();
        $where->equalTo('user_id', $userId);
        $where->equalTo('channel', 'mymunchado_' . $userId);
        $where->greaterThanOrEqualTo('pubnub_notification.created_on', $today);
        $select->where($where);
        $select->limit($limit);
        $select->order('pubnub_notification.id DESC');
        if ($type == 'one') {
            $notificationData = $this->_tableGateway->selectWith($select)->current();
            $userFunction = StaticFunctions::getServiceLocator()->get(UserFunctions::class);
            if ($notificationData) {
                $myCurrentNotification['id'] = $notificationData->id;
                $myCurrentNotification['msg'] = $userFunction->to_utf8($notificationData->msg);
                $myCurrentNotification['type'] = $notificationData->type;
                $myCurrentNotification['restaurant_id'] = $notificationData->restaurant_id;
                $myCurrentNotification['created_on'] = StaticFunctions::getFormattedDateTime($notificationData->created_on, 'Y-m-d H:i:s', 'Y-m-d H:i:s');
                $myCurrentNotification['classes'] = self::$imageClass[$notificationData->type];
                $myCurrentNotification['status'] = $notificationData->status;

                if ($notificationData->pubnub_info != '') {
                    $pubnubArray = json_decode($notificationData->pubnub_info);
                    foreach ($pubnubArray as $key => $val) {
                        if (trim($key) == 'order_id') {
                            $getArchiveOrder = $userOrderMoel->getArchiveOrderForNotification(trim($val), $todayDate);
                            if (count($getArchiveOrder) > 0) {
                                $myCurrentNotification['is_live'] = 0;
                            } else {
                                $myCurrentNotification['is_live'] = 1;
                            }
                        }
                        $myCurrentNotification[$key] = $val;
                    }
                }
                $myCurrentNotification['user_id'] = isset($myCurrentNotification['user_id']) ? (string) $myCurrentNotification['user_id'] : "";
                if (isset($notificationData->type)) {
                    $myCurrentNotification['classes'] = self::$imageClass[$notificationData->type];
                }
                if (isset($notificationData->type)) {
                    $myCurrentNotification['link'] = self::$orderType[$notificationData->type];
                }
            }
            return $myCurrentNotification;
        } elseif ($type == 'all') {
            $notificationlists = [];
            $notificationData = $this->_tableGateway->selectWith($select)->toArray();
            $i = 0;
            if (!empty($notificationData)) {
                foreach ($notificationData as $key => $value) {
                    if ($i > 0) {
                        $value['classes'] = self::$imageClass[$value['type']];
                        $value['link'] = $type[$value['type']];
                        if (!empty($value['created_on'])) {
                            $creationDate = StaticFunctions::getFormattedDateTime($value['created_on'], 'Y-m-d H:i:s', 'Y-m-d H:i:s');
                            $value['msg_time'] = $this->getDayDifference($creationDate, $todayDate);
                            $pubnub = isset($value['pubnub_info']) && $value['pubnub_info'] != '' ? json_decode($value['pubnub_info']) : [];
                            if (count($pubnub) > 0) {
                                foreach ($pubnub as $key => $val) {
                                    if (trim($key) == 'order_id') {
                                        $getArchiveOrder = $userOrderMoel->getArchiveOrderForNotification(trim($val), $todayDate);
                                        if (count($getArchiveOrder) > 0) {
                                            $value['is_live'] = 0;
                                        } else {
                                            $value['is_live'] = 1;
                                        }
                                    }
                                    $value[$key] = $val;
                                }
                            }
                            unset($value['pubnub_info']);
                            $value['user_id'] = isset($value['user_id']) ? (string) $value['user_id'] : "";
                            $notificationlists[] = $value;
                        }
                    }
                    $i ++;
                }
            }
            return $notificationlists;
        }
    }

    public function getDayDifference($creationDate, $todayDate) {
        $date1 = StaticFunctions::getFormattedDateTime($creationDate, 'Y-m-d H:i:s', 'Y-m-d'); // created on date
        $date5 = StaticFunctions::getFormattedDateTime($todayDate, 'Y-m-d H:i:s', 'Y-m-d'); // StaticFunctions::getDateTime ()->format ( 'Y-m-d' ); // today's date
        $today = $todayDate;
        $date7 = date("Y-m-d", strtotime('-7 days', strtotime($today)));
        if ($date1 <= $date5) {
            if ($date1 != $date5) {
                if ($date1 > $date7) {
                    for ($i = 1; $i <= 6; $i ++) {
                        $date6 = date("Y-m-d", strtotime('-' . $i . 'days', strtotime($date5)));
                        if ($date1 === $date6) {
                            if ($i == 1)
                                return date('M d, Y', strtotime($creationDate)) . " (" . $i . " day ago)"; // Feb 21, 2013 (1 day ago)
                            else
                                return date('M d, Y', strtotime($creationDate)) . " (" . $i . " days ago)"; // Feb 21, 2013 (2 days ago)
                        }
                    }
                } else {
                    return date('M d, Y', strtotime($creationDate)); // Feb 21, 2013
                }
            } elseif ($date1 === $date5) {
                if (date('H:i', strtotime($creationDate)) == '12:00') {
                    return "Today, " . date('h:i', strtotime($creationDate)) . " noon"; // Today, 12:00 noon
                } else {
                    return "Today, " . date('h:i a', strtotime($creationDate)); // Today, 6:35PM
                }
            }
        } else {
            return StaticFunctions::getFormattedDateTime($creationDate, 'Y-m-d H:i:s', 'M d, Y'); // date('M d, Y',strtotime($creation_date));
        }
    }

    public function countUserNotification($userId = 0, $todayDate = false) {
        $today = date('Y-m-d H:i:s', strtotime("-30 days", strtotime($todayDate)));
        $select = new Select();
        $select->from($this->_tableGateway->getTable());
        $select->columns(['notifications' => new Expression('count(id)')]);
        $where = new Where();
        $where->equalTo('user_id', $userId);
        $where->equalTo('read_status', 0);
        $where->equalTo('channel', 'mymunchado_' . $userId);
        $where->greaterThanOrEqualTo('pubnub_notification.created_on', $today);
        $select->where($where);
        $userNotifications = $this->_tableGateway->selectWith($select)->toArray();
        return $userNotifications;
    }

    public function getUserNotification($user_id = 0) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns([
            'id',
            'user_id',
            'notification_msg',
            'type',
            'restaurant_id',
            'created_on'
        ]);
        $where = new Where();
        $where->equalTo('user_id', $user_id);
        $where->equalTo('read_status', 0);
        $select->where($where);
        $select->order('id DESC');
        $userNotificationsDetails = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select);
        $data = $userNotificationsDetails->toArray();
        if (empty($data)) {
            $select->from($this->getDbTable()
                            ->getTableName());
            $select->columns([
                'id',
                'user_id',
                'notification_msg',
                'type',
                'restaurant_id',
                'created_on'
            ]);
            $where = new Where();
            $where->equalTo('user_id', $user_id);
            $where->equalTo('read_status', 1);
            $select->where($where);
            $select->order('id DESC');
            $select->limit(5);
            $userNotificationsDetails = $this->getDbTable()
                    ->setArrayObjectPrototype('ArrayObject')
                    ->getReadGateway()
                    ->selectWith($select);
            $data = $userNotificationsDetails->toArray();
        }
        return $data;
    }

    public function readNotificationList($user_id = 0) {
        $select = new Select();
        $select->from($this->getDbTable()
                        ->getTableName());
        $select->columns([
            'id',
            'user_id',
            'notification_msg',
            'type',
            'restaurant_id',
            'created_on'
        ]);
        $where = new Where();
        $where->equalTo('user_id', $user_id);
        $where->equalTo('read_status', 1);
        $select->where($where);
        $select->order('id DESC');
        $userNotificationsDetails = $this->getDbTable()
                ->setArrayObjectPrototype('ArrayObject')
                ->getReadGateway()
                ->selectWith($select);
        $data = $userNotificationsDetails->toArray();
        return $data;
    }

    public function notificationStatusChange($user_id = 0, $notificationId) {
        $data = ['read_status' => 1];
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $dataUpdated = [];
        $dataUpdated = $writeGateway->update($data, [
            'id' => $notificationId,
            'user_id' => $user_id
        ]);
        return $dataUpdated;
    }

    public function reservationNotifications($data) {
        if ($data['status'] == 'new') {
            $channel = "mymunchado_" . $data['user_id'];
            $message = "New reservation made at " . $data['restaurant_name'];
        }
        if ($data['status'] == 'update') {
            $channel = "mymunchado_" . $data['user_id'];
            $message = "Your reservation at " . $data['restaurant_name'] . " was modified.";
        }
        if ($data['status'] == 'cancelled') {
            $channel = "mymunchado_" . $data['user_id'];
            $message = 'Your reservation at ' . $data['restaurant_name'] . ' was cancelled';
        }
        $dataArray = array(
            'user_id' => $data['user_id'],
            'notification_msg' => $message,
            'type' => 3,
            'read_status' => 0,
            'restaurant_id' => $data['restaurant_id'],
            'channel' => $channel,
            'created_on' => $data['created_on']
        );
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($dataArray);
        // $pubnub = StaticFunctions::pubnubPushNotification($channel, $message);
        return $rowsAffected;
    }

    public function orderNotifications($data) {
        if ($data['status'] == 'new') {
            $channel = "mymunchado_" . $data['user_id'];
            $message = "Your Order Placed at " . $data['restaurant_name'];
        }
        $dataArray = array(
            'user_id' => $data['user_id'],
            'notification_msg' => $message,
            'type' => 1,
            'read_status' => 0,
            'restaurant_id' => $data['restaurant_id'],
            'channel' => $channel,
            'created_on' => $data['created_on']
        );
        $writeGateway = $this->getDbTable()->getWriteGateway();
        $rowsAffected = $writeGateway->insert($dataArray);
        // $pubnub = StaticFunctions::pubnubPushNotification($channel, $message);
        return $rowsAffected;
    }

    public function createPubNubNotification($data, $jsonData = array()) {
        $notId = isset($data['friend_iden_Id']) && $data['friend_iden_Id'] != '' ? $data['friend_iden_Id'] : $data['userId'];

        if (isset($data['channel']) && $data['channel'] != '') {
            $notificationTo = explode('_', $data['channel']);
        }

        if (!StaticFunctions::getPermissionToSendNotification($notId) && ($notificationTo[0] != "dashboard" || $notificationTo[0] != "cmsdashboard")) {
            return true;
        }
        if (!empty($data)) {
            if ($data['type'] == 'reservation') {
                $type = 3;
            } elseif ($data['type'] == 'cancelreservation') {
                $type = 15;
            } elseif ($data['type'] == 'order') {
                $type = 1;
            } elseif ($data['type'] == 'invite_friends') {
                $type = 5;
            } elseif ($data['type'] == 'reviews') {
                $type = 4;
            } elseif ($data['type'] == 'friendship') {
                $type = 10;
            } elseif ($data['type'] == 'tip') {
                $type = 7;
            } elseif ($data['type'] == 'upload_photo') {
                $type = 8;
            } elseif ($data['type'] == 'bookmark') {
                $type = 9;
            } elseif ($data['type'] == 'checkin') {
                $type = 11;
            } elseif ($data['type'] == 'feed') {
                $type = 12;
            } elseif ($data['type'] == 'dine_more') {
                $type = 17;
            } elseif ($data['type'] == 'bill') {
                $type = 18;
            } elseif ($data['type'] == 'snag-a-spot') {
                $type = 19;
            } else {
                $type = 0;
            }
            $jsonDataArray = '';
            if (count($jsonData) > 0) {
                $jsonDataArray = json_encode($jsonData, JSON_HEX_APOS);
            }
            $cUpdate = 0;
            if (isset($data['cronUpdate']) && $data['cronUpdate'] != '') {
                $cUpdate = $data['cronUpdate'];
            }
            $userId = isset($data['friend_iden_Id']) && $data['friend_iden_Id'] != '' ? $data['friend_iden_Id'] : $data['userId'];
            $dataArray = array(
                'user_id' => $userId,
                'notification_msg' => $data['msg'],
                'type' => $type,
                // 'read_status' => 0,
                'restaurant_id' => $data['restaurantId'],
                'channel' => $data['channel'],
                'created_on' => $data['curDate'],
                'pubnub_info' => $jsonDataArray,
                'cronUpdate' => $cUpdate
            );

            $rowsAffected = $this->_tableGateway->insert($dataArray);
            if ($rowsAffected) {
                $notification = $this->countUserNotification($data['userId']);
                $count = $notification[0]['notifications'];
                $pubnub = StaticFunctions::pubnubPushNotification(array(
                            'count' => $count,
                            'channel' => $data['channel']
                ));
                $pubnub = StaticFunctions::pubnubPushNotification(array(
                            'count' => $count,
                            'channel' => 'ios_' . $data['channel']
                ));
                $pubnub = StaticFunctions::pubnubPushNotification(array(
                            'count' => $count,
                            'channel' => 'android_' . $data['channel']
                ));
                return true;
            }
        }
    }

    public function update($data) {
        $rowsAffected = $this->_tableGateway->update($data, [
            'user_id' => $this->user_id,
            'channel' => 'mymunchado_' . $this->user_id
        ]);
        return ($rowsAffected) ? true : false;
    }

    public function getNotification(array $options = []) {
        return $this->find($options)->toArray();
    }

    public function updateCronNotification($id = false) {
        $rowsAffected = $this->_tableGateway->update(['cronUpdate' => 1],[
            'id' => $id
        ]);
        return ($rowsAffected) ? true : false;
    }

}
