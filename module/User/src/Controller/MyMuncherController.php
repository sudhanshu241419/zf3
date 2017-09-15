<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Functions\UserFunctions;

class MyMuncherController extends AbstractRestfulController {

    public function getList() {
        $myMuncher = [];
        $session = $this->getUserSession();
        $isLoggedIn = $session->isLoggedIn();
        $friendId = $this->getQueryParams('friendid', false);
        if ($isLoggedIn) {
            if ($friendId) {
                $userId = $friendId;
            } else {
                $userId = $session->getUserId();
            }
        } else {
            throw new \Exception('Not a valid user', 404);
        }
        $userFunctions = $this->getServiceLocator(UserFunctions::class);
        $allAvatar = $userFunctions->getAllAvatar();
        if ($allAvatar) {
            $myMuncher = $userFunctions->getMyAvatar($allAvatar, $userId);
        }
        return $myMuncher;
    }

}
