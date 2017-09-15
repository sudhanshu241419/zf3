<?php

namespace Auth\Model;

use Zend\Db\TableGateway\TableGatewayInterface;
use MCommons\Model\AbstractModel;
use MCommons\StaticFunctions;

class UserSession extends AbstractModel {

    public $id;
    public $token;
    public $user_details;
    public $user_id;
    public $ttl;
    public $created_at;
    public $last_update_timestamp;
    protected $_user_data = array();
    protected $_redisCache = false;
    protected $_tableGateway;

    public function __construct(TableGatewayInterface $tableGateway) {
        parent::__construct($tableGateway);
        $this->_tableGateway = $tableGateway;
        $this->_redisCache = StaticFunctions::getRedisCache();
    }

    /**
     * Return false if save was not successfull else return the updated object
     *
     * @return \Auth\Model\Token boolean
     */
    public function save() {
        $data = $this->toArray();
        $prevUserDetails = @unserialize($this->user_details);
        if (!$prevUserDetails) {
            $prevUserDetails = array();
        }
        $this->_user_data = @array_replace_recursive($prevUserDetails, $this->_user_data);
        $data ['user_details'] = @serialize($this->_user_data);

        if (!$this->token) {
            throw new \Exception("Invalid user. User may have expired", 403);
        } else {
            if ($this->_redisCache) {
                $this->_redisCache->replaceItem($this->token, $data);
            } else {
                $rowsAffected = $this->_tableGateway->update($data, array(
                    'token' => $this->token
                        ));
            }
        }
        return true;
    }

    public function delete() {

        if (!$this->token) {
            throw new \Exception("Invalid token provided", 500);
        } else {
            if ($this->_redisCache) {
                if ($this->_redisCache->hasItem($this->token)) {
                    $this->_redisCache->removeItem($this->token);
                    return 1;
                }
                return 0;
            }
            $rowsAffected = $this->_tableGateway->delete(array(
                'token' => $this->token
                    ));
        }
        return $rowsAffected;
    }

    public function getUserDetail($key = false, $defaultValue = false) {
        $prevUserDetails = @unserialize($this->user_details);
        if (!$prevUserDetails) {
            $prevUserDetails = [];
        }
        $allDetails = @array_replace_recursive($prevUserDetails, $this->_user_data);
        if (!$key) {
            return $allDetails;
        }
        if (!isset($allDetails [$key])) {
            return $defaultValue;
        }
        return $allDetails [$key];
    }

    /**
     * Set the details of user
     *
     * @param string $key
     * @param string $value
     * @throws \Exception
     * @return \Auth\Model\UserSession
     */
    public function setUserDetail($key = false, $value = false) {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->_user_data [$k] = $v;
            }
        } else if (!$key) { //|| $value
            throw new \Exception("Invalid key sent for setting user detail");
        } else {
            $this->_user_data [$key] = $value;
        }
        return $this;
    }

    public function isLoggedIn() {
        if ((int) $this->user_id) {
            return true;
        }
        return false;
    }

    public function getUserId() {
        return (int) $this->user_id;
    }

    public function setUserId($id) {
        $this->user_id = (int) ($id);
        return $this;
    }

}
