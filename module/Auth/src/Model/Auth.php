<?php

namespace Auth\Model;

use RuntimeException;
use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use MCommons\StaticFunctions;

class Auth extends AbstractModel {

    protected $_redisCache = false;
    public $id;
    public $token;
    public $user_details;
    public $user_id;
    public $ttl;
    public $created_at;
    public $last_update_timestamp;
    protected $_user_data;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
        $this->_redisCache = StaticFunctions::getRedisCache();
    }

    public function fetchAll() {
        return $this->_tableGateway->select();
    }

    public function save() {
        $data = $this->toArray();
        if ($this->_redisCache) {
            $this->_redisCache->setItem($this->token, $data);
            $data['id'] = $this->token;
            return $this;
        }

        if (!$this->id) {
            $rowsAffected = $this->_tableGateway->insert($data);
        } else {
            $rowsAffected = $this->_tableGateway->update($data, array(
                'id' => $this->id
                    ));
        }
        //pr($this->_tableGateway->getSql()->insert(),1);
        if ($rowsAffected >= 1) {
            $this->id = $this->_tableGateway->lastInsertValue;
            return $this;
        }
        return false;
    }
    
    public function update($data,$condition){
         $rowsAffected = $this->_tableGateway->update($data, $condition);
         if ($rowsAffected >= 1) {
             return true;
         }
         return false;
    }

    public function findToken($token) {

        if (!$token) {
            throw new RuntimeException(sprintf('Token is empty', $token));
        }

        if ($this->_redisCache) {
            if ($this->_redisCache->hasItem($token)) {

                $data = $this->_redisCache->getItem($token);
                $data['user_details'] = @serialize($data['user_details']);
                $this->exchangeArray($data);
                return $this;
            }

            return false;
        }

        return $this->find(array(
                    'where' => array(
                        'token' => $token
                    )
                ))->current();
    }
    
    
    public function findToken2($token) {

        if (!$token) {
            throw new RuntimeException(sprintf('Token is empty', $token));
        }

        if ($this->_redisCache) {
            if ($this->_redisCache->hasItem($token)) {

                $data = $this->_redisCache->getItem($token);
                $data['user_details'] = @serialize($data['user_details']);
                $this->exchangeArray($data);
                return $this;
            }

            return false;
        }

        return $this->find(array(
                    'where' => array(
                        'token' => $token
                    )
                ))->toArray();
    }

    public function delete($token) {
        return false;
        $deleted = $this->_tableGateway->delete($token);
        if (!$deleted) {
            return false;
        }
        return true;
    }
    
     public function findExpireTimeToken($token) {
        if ($this->_redisCache) {
            if ($this->_redisCache->hasItem($token)) {
                $data = $this->_redisCache->getItem($token);
                $data['user_details'] = @serialize($data['user_details']);
                $tokenExpireTime = (int) $data['ttl'] + (int) $data['last_update_timestamp'];
                return $tokenExpireTime;
            }
            return false;
        }
        return false;
    }

    ############## Session Details #################

//    public function sessionSave() {
//        $data = $this->toArray();
//        $prevUserDetails = @unserialize($this->user_details);
//        if (!$prevUserDetails) {
//            $prevUserDetails = array();
//        }
//        $this->_user_data = @array_replace_recursive($prevUserDetails, $this->_user_data);
//        $data ['user_details'] = @serialize($this->_user_data);
//
//        if (!$this->token) {
//            throw new \Exception("Invalid user. User may have expired", 403);
//        } else {
//            if ($this->_redisCache) {
//                $this->_redisCache->replaceItem($this->token, $data);
//            } else {
//                $rowsAffected = $this->_tableGateway->update($data, array(
//                    'token' => $this->token
//                        ));
//            }
//        }
//        return true;
//    }
//
//    public function sessionDelete() {
//        if (!$this->token) {
//            throw new \Exception("Invalid token provided", 500);
//        } else {
//            if ($this->_redisCache) {
//                if ($this->_redisCache->hasItem($this->token)) {
//                    $this->_redisCache->removeItem($this->token);
//                    return 1;
//                }
//                return 0;
//            }
//            $rowsAffected = $this->_tableGateway->delete(array(
//                'token' => $this->token
//                    ));
//        }
//        return $rowsAffected;
//    }
//
//    public function getUserDetail($key = false, $defaultValue = false) {
//        $prevUserDetails = @unserialize($this->user_details);
//        pr( $this->_user_data,1);
//        if (!$prevUserDetails) {
//            $prevUserDetails = array();
//        }
//        $allDetails = @array_replace_recursive($prevUserDetails, $this->_user_data);
//        pr($allDetails,1);
//        if (!$key) {
//            return $allDetails;
//        }
//        if (!isset($allDetails [$key])) {
//            return $defaultValue;
//        }
//        return $allDetails [$key];
//    }
//
//    /**
//     * Set the details of user
//     *
//     * @param string $key
//     * @param string $value
//     * @throws \Exception
//     * @return \Auth\Model\UserSession
//     */
//    public function setUserDetail($key = false, $value = false) {
//        if (is_array($key)) {
//            foreach ($key as $k => $v) {
//                $this->_user_data [$k] = $v;
//            }
//        } else if (!$key) { //|| $value
//            throw new \Exception("Invalid key sent for setting user detail");
//        } else {
//            $this->_user_data [$key] = $value;
//        }
//        return $this;
//    }
//
//    public function isLoggedIn() {
//        if ((int) $this->user_id) {
//            return true;
//        }
//        return false;
//    }
//
//    public function getUserId() {
//        return (int) $this->user_id;
//    }
//
//    public function setUserId($id) {
//        $this->user_id = (int) ($id);
//        return $this;
//    }  

}
