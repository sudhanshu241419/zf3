<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\UserNotification;
use User\Model\UserOrder;
use User\Model\UserReservation;
use MCommons\StaticFunctions;
use User\Functions\UserFunctions;

class UserCurrentNotificationController extends AbstractRestfulController {

    public function getList() {
        $notificationModel = $this->getServiceLocator(UserNotification::class);
        $UserReservationModel = $this->getServiceLocator(UserReservation::class);
        $UesrOrderModel = $this->getServiceLocator(UserOrder::class);
        $userFunction = $this->getServiceLocator(UserFunctions::class);
        $type = $this->getQueryParams('type');
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        if ($isLoggedIn) {
            $userId = $session->getUserId();
        } else {
            throw new \Exception('User detail not found', 404);
        }
        $locationData = $session->getUserDetail('selected_location');
        $currentDate = $userFunction->userCityTimeZone($locationData);
        if ($type == 'count') {
            $notification = $notificationModel->countUserNotification($userId, $currentDate);
            $count = $notification[0]['notifications'];
            return array('count' => $count);
        } else {
            try {
                $limit = 1;
                $notificationOne = [];
                $userCurrentNotification = $notificationModel->getCurrentNotification($userId, $limit, 'one', $currentDate);
                if (!empty($userCurrentNotification)) {
                    $today = time();
                    $strTime = "";
                    $creationTime = strtotime($userCurrentNotification['created_on']);
                    $creationDate = StaticFunctions::getFormattedDateTime($userCurrentNotification['created_on'], 'Y-m-d H:i:s', 'Y-m-d H:i:s'); // date('Y-m-d',strtotime($user_current_notification['created_date']));
                    $strTime = $notificationModel->getDayDifference($creationDate, $currentDate);
                    $userCurrentNotification['msg_time'] = $strTime;

                    if ($userCurrentNotification['type'] == 1 || $userCurrentNotification['type'] == 2) {
                        $currentNotifications = $UesrOrderModel->getCurrentNotificationOrder($userId, $currentDate);
                    } else {
                        if ($userCurrentNotification['type'] == 3) {
                            $currentNotifications = $UserReservationModel->getCurrentNotificationReservation($userId, $currentDate);
                        }
                    }
                    if (!empty($userCurrentNotification)) {
                        $notificationOne[] = $userCurrentNotification;
                    }
                }
                $limit = 10;
                $userAllNotification = $notificationModel->getCurrentNotification($userId, $limit, 'all', $currentDate);
                if (!empty($userAllNotification)) {
                    $notificationAll = $userAllNotification;
                    $notifications = array_merge($notificationOne, $notificationAll);
                } else {
                    $notifications = $notificationOne;
                }
                return $notifications;
            } catch (\Exception $e) {
                \MUtility\MunchLogger::writeLog($e, 1, 'Something went Wrong In Notification Api');
                throw new \Exception($e->getMessage(), 400);
            }
        }
    }

    public function update($id, $data) {
        $userId = $this->getUserSession()->getUserId();
        $userNotification = $this->getServiceLocator(UserNotification::class);
        if (isset($id)) {
            $options = [
                'where' => [
                    'user_id' => $userId,
                    'read_status' => 0,
                    'channel' => 'mymunchado_' . $userId
                ]
            ];
            $unreadNotification = $userNotification->getNotification($options);
            $notificationMsg = '';
            $channel = "mymunchado_" . $userId;
            $notificationArray = [
                "msg" => $notificationMsg,
                "channel" => $channel,
                "userId" => $userId,
                "type" => 'other',
                "restaurantId" => 0,
                "sendcountzero" => 0,
                'curDate' => date('y-m-d h:i:s')
            ];
            //$pubnub = StaticFunctions::pubnubPushNotification($notificationArray);
            if (!empty($unreadNotification)) {
                $userNotification->user_id = $userId;
                $data = ['read_status' => 1];
                $userNotification->update($data);
                return [
                    'success' => true,
                    'count' => 0
                ];
            } else {
                throw new \Exception("Status is already updated.");
            }
        }
    }

}
