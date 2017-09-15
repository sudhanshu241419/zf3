<?php

namespace Auth\Controller;

use Auth\Model\Auth;
use MCommons\Controller\AbstractRestfulController;
use MCommons\StaticFunctions;

class AuthController extends AbstractRestfulController {

    private $authModel;

    public function getList() {
        
    }

    public function get($token) {

        $this->authModel = $this->getServiceLocator(Auth::class);
        
        try {
            $response = array(
                'valid' => false
            );

            $authDetails = $this->authModel->findToken($token);

            if ($authDetails) {
                $currTimestamp = StaticFunctions::getDateTime()->getTimestamp();
                if ($currTimestamp < ((int) $authDetails->ttl + (int) $authDetails->last_update_timestamp)) {
                    $userDetails = @unserialize($authDetails->user_details);
                    $response['valid'] = true;
                    $response['user_details'] = $userDetails ? $userDetails : array();
                    $response['user_id'] = (int) $authDetails->user_id;
                    if ($this->isMobile()) {
                        $userDetails['user_id'] = (int) $authDetails->user_id;
                        $response = $userDetails;
                    }
                }
            }
            $this->response->setHeaders(StaticFunctions::getExpiryHeaders());            
            return $response;
        } catch (\Exception $e) {
            pr($e->getMessage(), 1);
        }
    }

    public function create($data) {
        try {
            $tokenTime = microtime();
            $salt = "Munc!";
            $token = md5($salt . $tokenTime);
            $this->authModel = $this->getServiceLocator(Auth::class);
            $this->authModel->token = $token;
            $this->authModel->ttl = $this->getDefaultTtl();
            $this->authModel->created_at = StaticFunctions::getDateTime()->format(StaticFunctions::MYSQL_DATE_FORMAT);
            $this->authModel->last_update_timestamp = StaticFunctions::getDateTime()->getTimestamp();

            if (!$this->authModel->save()) {
                return $this->sendError(array('error' => 'Unable to save data'), 500);
            }
            $this->response->setHeaders(StaticFunctions::getExpiryHeaders());
            return array("token" => $token);
        } catch (\Exception $e) {
            \MUtility\MunchLogger::writeLog($e, 1, 'Something Went Wrong On Token Api');
            throw new \Exception($e->getMessage(), 400);
        }
    }

    public function update($id, $data) {
        
    }

    public function delete($id) {
        
    }

    protected function getDefaultTtl() {
        $config = $this->getServiceLocator('config');
        $apiStandards = isset($config['api_standards']) ? $config['api_standards'] : array();
        $defaultTtl = isset($apiStandards['default_ttl']) ? $apiStandards['default_ttl'] : 315360000;
        return $defaultTtl;
    }

}
