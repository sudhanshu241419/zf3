<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;

class LoginController extends AbstractRestfulController {

    public function create($data) {
        if (!isset($data['type']) || empty($data['type'])) {
            throw new \Exception('Please provide the type of login');
        }
        $userfunctions = new \User\Functions\UserFunctions();
        switch ($data['type']) {
            case 'normal':
                return $userfunctions->normalLogin($data);
                break;
            case 'facebook':
                return $userfunctions->facebookLogin($data);
                break;
            case 'google':
                return $userfunctions->googleLogin($data);
                break;
            case 'twitter':
                return $userfunctions->twitterLogin($data);
                break;
            default:
                throw new \Exception('No such type of login exists', 400);
        }
    }

}
