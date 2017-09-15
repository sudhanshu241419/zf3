<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\User;

class ChangePasswordController extends AbstractRestfulController {

    const FORCE_LOGIN = true;

    public function update($id, $data) {
        if (empty($data['current_password'])) {
            throw new \Exception("Woah now, we can't let you go without a current password.", 400);
        }
        if (!empty($data['current_password']) && !empty($data ['new_password'])) {
            if ($data['current_password'] == $data ['new_password']) {
                throw new \Exception("Woah now, Your current password & new password should not be same.", 400);
            }
        }

        if (!empty($data ['new_password']) && strlen($data ['new_password']) >= 6) {
            if ($data ['new_password'] != $data ['confirm_password']) {
                throw new \Exception(" Woah now, your new password and confirm password is not match", 400);
            }
            $userModel = $this->getServiceLocator(User::class);
            $session = $this->getUserSession();
            $userModel->id = $session->getUserId();
            if (!$userModel->id) {
                throw new \Exception("Woah now, You are not valid user.", 400);
            }
            $currentPassword = md5(trim($data ['current_password']));
            $option = ['where' => ['id' => $userModel->id, 'password' => $currentPassword]];
            $userDetail = $userModel->getUserDetail($option);
            if ($userDetail) {
                $data1 = ['password' => md5($data ['new_password'])];
                $userModel->update($data1);
                return ['success' => 'true'];
            } else {
                throw new \Exception("That's not your current password, are you sure you're you?", 400);
            }
        } else {
            throw new \Exception("You need to use at least 6 characters for new password.", 400);
        }
    }

}
