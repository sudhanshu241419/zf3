<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\UserActionSettings;

class UserActionSettingController extends AbstractRestfulController {

    function getList() {
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        $userActionSettings = $this->getServiceLocator(UserActionSettings::class);
        if (!$isLoggedIn) {
            throw new \Exception('User unavailable', 400);
        }
        $userId = $session->getUserId();
        
        $response = $userActionSettings->userActionSettings(['where' => ['user_id' => $userId]]);
        if (isset($response[0])) {
            foreach ($response[0] as $key => $val) {
                if ($key === 'created_at' || $key === 'updated_at') {
                    
                } else {
                    $responseData[$key] = intval($val);
                }
            }
        } else {
            $responseData['id'] = intval(0);
            $responseData['user_id'] = intval($userId);
            $responseData['order'] = intval(0);
            $responseData['reservation'] = intval(0);
            $responseData['bookmarks'] = intval(0);
            $responseData['checkin'] = intval(0);
            $responseData['muncher_unlocked'] = intval(0);
            $responseData['upload_photo'] = intval(0);
            $responseData['reviews'] = intval(0);
            $responseData['tips'] = intval(0);
            $responseData['email_sent'] = intval(1);
            $responseData['sms_sent'] = intval(1);
            $responseData['notification_sent'] = intval(1);
        }
        return $responseData;
    }

}
