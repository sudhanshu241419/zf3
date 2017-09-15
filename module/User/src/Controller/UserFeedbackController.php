<?php

namespace User\Controller;

use MCommons\Controller\AbstractRestfulController;
use User\Model\UserFeedback;

class UserFeedbackController extends AbstractRestfulController {

    const FORCE_LOGIN = true;

    public function create($data) {
        $userId = $this->getUserSession()->getUserId();
        if (!$userId) {
            throw new \Exception('User id not found');
        }
        if (!isset($data ['review_id'])) {
            throw new \Exception('Review id is required');
        }
        if (!isset($data ['feedback'])) {
            throw new \Exception('Feedback is required');
        }
        $userFeedbackModel = $this->getServiceLocator(UserFeedback::class);
        $options = array(
            'columns' => array(
                'count' => new \Zend\Db\Sql\Expression('COUNT(*)')
            ),
            'where' => array(
                'review_id' => $data ['review_id'],
                'user_id' => $userId
            )
        );
        $count = $userFeedbackModel->find($options)->current()->getArrayCopy();
        if ($count ['count'] > 0) {
            return array(
                'success' => true
            );
        }
        $userFeedbackModel->review_id = $data ['review_id'];
        $userFeedbackModel->feedback = $data ['feedback'];
        $userFeedbackModel->user_id = $userId;
        $userFeedbackModel->addFeedback();
        return array(
            'success' => true
        );
    }

}
